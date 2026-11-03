<?php

declare(strict_types=1);

/** Reads the single PHP Vars configuration; environment overrides remain supported. */
final class GBDBConfig {
    private static ?array $values = null;

    public static function root() :string {
        return dirname(__DIR__);
    }

    public static function get(string $section, string $name, mixed $default = null) :mixed {
        $environment = getenv('GBDB_' . strtoupper(str_replace('.', '_', $section . '_' . $name)));

        if ($environment !== false) {
            return $environment;
        }

        if (self::$values === null) {
            self::$values = self::constantsToSections(self::editableConstants());
        }

        return self::$values[$section][$name] ?? $default;
    }

    public static function file() :string {
        return self::root() . '/.config/.framework.env.php';
    }

    /** Constant values only: editing configuration never executes submitted PHP. */
    public static function editableConstants() :array {
        if (!class_exists('Vars', false)) {
            if (!is_file(self::file())) {
                throw new RuntimeException('Die .framework.env.php mit der Vars-Klasse fehlt.');
            }

            require_once self::file();
        }

        if (!class_exists('Vars', false) || !method_exists('Vars', 'configuration')) {
            throw new RuntimeException('.framework.env.php muss die Vars-Konfiguration enthalten.');
        }

        return (new ReflectionClass(Vars::class))->getConstants();
    }

    private static function validateValue(mixed $value, mixed $original, string $path) :void {
        if (get_debug_type($value) !== get_debug_type($original)) {
            throw new InvalidArgumentException($path . ': Datentyp muss ' . get_debug_type($original) . ' bleiben.');
        }

        if (!is_array($original)) {
            return;
        }

        if (array_is_list($original)) {
            if (!array_is_list($value)) {
                throw new InvalidArgumentException($path . ': Liste erwartet.');
            }

            foreach ($value as $entry) {
                if ($original !== []) {
                    self::validateValue($entry, $original[0], $path . '[]');
                }
            }

            return;
        }

        if (array_diff_key($original, $value) || array_diff_key($value, $original)) {
            throw new InvalidArgumentException($path . ': Vorhandene Einstellungsnamen beibehalten.');
        }

        foreach ($original as $key => $entry) {
            self::validateValue($value[$key], $entry, $path . '.' . $key);
        }
    }

    public static function constantsToSections(array $constants) :array {
        $original = self::editableConstants();

        if (array_diff_key($original, $constants) || array_diff_key($constants, $original)) {
            throw new InvalidArgumentException('Alle Konfigurationsbereiche müssen erhalten bleiben.');
        }

        foreach ($original as $name => $value) {
            if ($name === 'FAILOVER_DEV') {
                if (!is_array($constants[$name])) {
                    throw new InvalidArgumentException('FAILOVER_DEV muss ein Array sein.');
                }

                foreach ($constants[$name] as $key => $entry) {
                    if (!in_array($key, ['dsn', 'user', 'password', 'prefix'], true) || !is_string($entry)) {
                        throw new InvalidArgumentException('FAILOVER_DEV erlaubt dsn, user, password und prefix als Zeichenketten.');
                    }
                }

                continue;
            }

            self::validateValue($constants[$name], $value, $name);
        }

        return Vars::configuration($constants);
    }

    private static function phpValue(mixed $value, int $indent = 4) :string {
        if (!is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $list = array_is_list($value);
        $lines = [];

        foreach ($value as $key => $entry) {
            $lines[] = str_repeat(' ', $indent + 4) . ($list ? '' : var_export($key, true) . ' => ')
                . self::phpValue($entry, $indent + 4) . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n" . str_repeat(' ', $indent) . ']';
    }

    /** Keeps methods and guards verbatim; only changed constant literals are replaced. */
    public static function sourceWithConstants(array $constants) :string {
        self::constantsToSections($constants);
        $original = self::editableConstants();
        $raw = file_get_contents(self::file());

        if ($raw === false) {
            throw new RuntimeException('Konfigurationsdatei ist nicht lesbar.');
        }

        $tokens = token_get_all($raw, TOKEN_PARSE);
        $result = '';
        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            $result .= is_array($token) ? $token[1] : $token;

            if (!is_array($token) || $token[0] !== T_CONST) {
                continue;
            }

            do {
                $token = $tokens[++$index];
                $result .= is_array($token) ? $token[1] : $token;
            } while (!is_array($token) || $token[0] !== T_STRING);

            $name = $token[1];

            if (!array_key_exists($name, $constants) || $constants[$name] === $original[$name]) {
                continue;
            }

            while ($tokens[++$index] !== '=') {
                $token = $tokens[$index];
                $result .= is_array($token) ? $token[1] : $token;
            }

            $result .= '= ' . self::phpValue($constants[$name]);

            while ($tokens[++$index] !== ';') {
                // Literal values cannot contain statement separators outside strings.
            }

            $result .= ';';
        }

        token_get_all($result, TOKEN_PARSE);

        return $result;
    }

    public static function flag(string $section, string $name, bool $default = false) :bool {
        $value = self::get($section, $name, $default);
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new InvalidArgumentException('Ungültiger Schalter: ' . $section . '.' . $name);
        }

        return $parsed;
    }

    public static function dataPath() :string {
        $path = (string) self::get('storage', 'path', 'GBDB_GQL/.DB');

        if ($path === '' || str_contains($path, '://') || str_contains($path, "\0")) {
            throw new InvalidArgumentException('storage.path muss ein Dateisystempfad sein.');
        }

        if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = self::root() . '/' . $path;
        }

        return rtrim(str_replace('\\', '/', $path), '/');
    }

    public static function runtimePath() :string {
        return self::root() . '/.config/.runtime';
    }

    public static function secret(string $name) :string {
        $configured = (string) self::get('security', $name, '');

        if ($configured !== '') {
            return $configured;
        }

        $directory = self::runtimePath();

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('GBDB kann sein geschütztes Konfigurationsverzeichnis nicht erstellen.');
        }

        $file = $directory . '/' . $name . '.php';
        $lock = fopen($directory . '/secrets.lock', 'c+');

        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('GBDB kann den Installationsschlüssel nicht sperren.');
        }

        try {
            if (is_file($file)) {
                $value = require $file;

                if (!is_string($value) || strlen($value) < 32) {
                    throw new RuntimeException('Ungültiger GBDB-Installationsschlüssel.');
                }

                return $value;
            }

            $value = bin2hex(random_bytes(32));
            $payload = "<?php\nreturn " . var_export($value, true) . ";\n";
            $temporary = $file . '.' . bin2hex(random_bytes(8));

            if (file_put_contents($temporary, $payload, LOCK_EX) !== strlen($payload) || !rename($temporary, $file)) {
                @unlink($temporary);
                throw new RuntimeException('GBDB kann den Installationsschlüssel nicht sichern.');
            }

            @chmod($file, 0600);

            return $value;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** Both environments inherit the same fallback unless an explicit DEV override exists. */
    public static function sql() :array {
        $result = [
            'dsn' => (string) self::get('failover', 'dsn', ''),
            'user' => (string) self::get('failover', 'user', ''),
            'password' => (string) self::get('failover', 'password', ''),
            'prefix' => (string) self::get('failover', 'prefix', 'gbdb_full'),
        ];

        if (Vars::__DEV__()) {
            foreach (['dsn', 'user', 'password', 'prefix'] as $name) {
                $value = self::get('failover.dev', $name);

                if ($value !== null) {
                    $result[$name] = (string) $value;
                }
            }
        }

        return $result;
    }
}
