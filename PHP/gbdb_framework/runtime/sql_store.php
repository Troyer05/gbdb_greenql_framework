<?php

declare(strict_types=1);

/** SQL storage preserves the complete GBDB format, including schema, indexes and GreenQL. */
final class GBDBSQLStore {
    private PDO $pdo;
    private string $files;
    private string $meta;
    private string $driver;
    /** @var array<string, PDOStatement> */
    private array $statements = [];

    public function __construct(array $configuration) {
        $prefix = (string) $configuration['prefix'];

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,39}$/D', $prefix)) {
            throw new InvalidArgumentException('Ungültiges SQL-Tabellenpräfix.');
        }

        $this->files = $prefix . '_files';
        $this->meta = $prefix . '_meta';
        $this->pdo = new PDO($configuration['dsn'], $configuration['user'], $configuration['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => max(1, min(30, (int) GBDBConfig::get('failover', 'connect_timeout', 3))),
        ]);
        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if (!in_array($this->driver, ['mysql', 'sqlite'], true)) {
            throw new InvalidArgumentException('SQL-Fallback unterstützt MySQL/MariaDB und SQLite.');
        }

        // DDL runs before any data transaction; MySQL DDL implicitly commits.
        if ($this->driver === 'mysql') {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->files}` (
                path_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
                parent_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                path TEXT NOT NULL,
                kind CHAR(1) NOT NULL,
                payload LONGBLOB NOT NULL,
                checksum CHAR(64) NOT NULL,
                size BIGINT NOT NULL,
                modified BIGINT NOT NULL,
                INDEX parent_lookup (parent_hash)
            ) ENGINE=InnoDB");
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->meta}` (id INT PRIMARY KEY, payload LONGTEXT NOT NULL) ENGINE=InnoDB");

            $engine = $this->query(
                'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$this->files]
            )->fetchColumn();

            if (strtolower((string) $engine) !== 'innodb') {
                throw new RuntimeException('Der SQL-Fallback benötigt InnoDB.');
            }
        } else {
            $this->pdo->exec('PRAGMA busy_timeout=10000');
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->files}` (
                path_hash TEXT PRIMARY KEY, parent_hash TEXT NOT NULL, path TEXT NOT NULL,
                kind TEXT NOT NULL, payload BLOB NOT NULL, checksum TEXT NOT NULL,
                size INTEGER NOT NULL, modified INTEGER NOT NULL
            )");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS `{$this->files}_parent` ON `{$this->files}` (parent_hash)");
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->meta}` (id INTEGER PRIMARY KEY, payload TEXT NOT NULL)");
        }
    }

    private function query(string $sql, array $parameters = []) :PDOStatement {
        $statement = $this->statements[$sql] ??= $this->pdo->prepare($sql);
        $statement->closeCursor();
        $statement->execute($parameters);

        return $statement;
    }

    public function state() :?array {
        $statement = $this->query("SELECT payload FROM `{$this->meta}` WHERE id = 1");
        $payload = $statement->fetchColumn();
        $statement->closeCursor();

        if ($payload === false) {
            return null;
        }

        $state = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($state) || !in_array($state['mode'] ?? '', ['gbdb', 'sql'], true)) {
            throw new RuntimeException('Ungültiger SQL-Umschaltstatus.');
        }

        return $state;
    }

    public function setState(array $state) :void {
        $payload = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $suffix = $this->driver === 'mysql'
            ? 'ON DUPLICATE KEY UPDATE payload = VALUES(payload)'
            : 'ON CONFLICT(id) DO UPDATE SET payload = excluded.payload';
        $this->query("INSERT INTO `{$this->meta}` (id, payload) VALUES (1, ?) {$suffix}", [$payload]);
    }

    public function entry(string $path, bool $withPayload = true) :?array {
        $columns = $withPayload ? '*' : 'path, kind, checksum, size, modified';
        $statement = $this->query("SELECT {$columns} FROM `{$this->files}` WHERE path_hash = ?", [hash('sha256', $path)]);
        $row = $statement->fetch();

        if (is_array($row) && is_resource($row['payload'] ?? null)) {
            $row['payload'] = stream_get_contents($row['payload']);
        }

        $statement->closeCursor();

        return $row === false ? null : $row;
    }

    public function read(string $path) :string {
        $entry = $this->entry($path);

        if ($entry === null || $entry['kind'] !== 'f') {
            throw new RuntimeException('SQL-Datei fehlt: ' . $path);
        }

        $payload = is_resource($entry['payload']) ? stream_get_contents($entry['payload']) : (string) $entry['payload'];

        if (strlen($payload) !== (int) $entry['size'] || ($entry['checksum'] !== '' && !hash_equals($entry['checksum'], hash('sha256', $payload)))) {
            throw new RuntimeException('Prüfsummenfehler im SQL-Datenbestand: ' . $path);
        }

        return $payload;
    }

    public function put(string $path, string $payload = '', bool $directory = false) :void {
        $parent = $path === '' ? '' : (dirname($path) === '.' ? '' : dirname($path));
        $suffix = $this->driver === 'mysql'
            ? 'ON DUPLICATE KEY UPDATE parent_hash=VALUES(parent_hash), path=VALUES(path), kind=VALUES(kind), payload=VALUES(payload), checksum=VALUES(checksum), size=VALUES(size), modified=VALUES(modified)'
            : 'ON CONFLICT(path_hash) DO UPDATE SET parent_hash=excluded.parent_hash, path=excluded.path, kind=excluded.kind, payload=excluded.payload, checksum=excluded.checksum, size=excluded.size, modified=excluded.modified';
        $this->query(
            "INSERT INTO `{$this->files}` (path_hash,parent_hash,path,kind,payload,checksum,size,modified) VALUES (?,?,?,?,?,?,?,?) {$suffix}",
            [hash('sha256', $path), hash('sha256', $parent), $path, $directory ? 'd' : 'f', $payload, hash('sha256', $payload), strlen($payload), time()]
        );
    }

    public function append(string $path, string $payload) :void {
        if ($this->entry($path, false) === null) {
            $this->put($path, $payload);

            return;
        }

        if ($this->driver === 'mysql') {
            $this->query(
                "UPDATE `{$this->files}` SET checksum=SHA2(CONCAT(payload,?),256), payload=CONCAT(payload,?), size=size+?, modified=? WHERE path_hash=? AND kind='f'",
                [$payload, $payload, strlen($payload), time(), hash('sha256', $path)]
            );
        } else {
            $this->put($path, $this->read($path) . $payload);
        }
    }

    public function children(string $path) :array {
        $rows = $this->query("SELECT path FROM `{$this->files}` WHERE parent_hash=? AND path_hash<>?", [hash('sha256', $path), hash('sha256', $path)])->fetchAll();

        return array_map(static fn(array $row) :string => basename($row['path']), $rows);
    }

    /** @return Generator<int, array<string, mixed>> */
    public function entries() :Generator {
        $statement = $this->pdo->query("SELECT path,kind,checksum,size,modified FROM `{$this->files}` ORDER BY LENGTH(path),path");

        try {
            while ($row = $statement->fetch()) {
                yield $row;
            }
        } finally {
            $statement->closeCursor();
        }
    }

    public function delete(string $path) :void {
        $this->query("DELETE FROM `{$this->files}` WHERE path_hash=?", [hash('sha256', $path)]);
    }

    public function rename(string $from, string $to) :void {
        $source = $this->entry($from);

        if ($source === null) {
            throw new RuntimeException('Die SQL-Quelle zum Umbenennen fehlt.');
        }

        if ($source['kind'] === 'f') {
            $this->transaction(function () use ($from, $to) :void {
                $this->put($to, $this->read($from));
                $this->delete($from);
            });

            return;
        }

        $this->transaction(function () use ($from, $to) :void {
            $all = iterator_to_array($this->entries());

            foreach ($all as $entry) {
                if ($entry['path'] === $from || str_starts_with($entry['path'], $from . '/')) {
                    $target = $to . substr($entry['path'], strlen($from));
                    $this->put($target, $entry['kind'] === 'd' ? '' : $this->read($entry['path']), $entry['kind'] === 'd');
                    $this->delete($entry['path']);
                }
            }
        });
    }

    public function reset() :void {
        $this->pdo->exec("DELETE FROM `{$this->files}`");
    }

    public function transaction(callable $callback) :mixed {
        $outer = !$this->pdo->inTransaction();

        if ($outer) {
            $this->pdo->beginTransaction();
        }

        try {
            $result = $callback();

            if ($outer) {
                $this->pdo->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($outer && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
