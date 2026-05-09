<?php
declare(strict_types=1);

/**
 * Developer API for GBDB: short, fluent query builder wrapper.
 *
 * example:
 * GBDBQueryBuilder::table('main', 'users')
 *   ->where('email', 'demo@example.com')
 *   ->limit(1)
 *   ->get();
 */
class GBDBQueryBuilder {
    private string $database = '';
    private string $table = '';
    private array $where = [];
    private array $order = [];
    private int $limit = 0;
    private int $offset = 0;

    public static function table(string $database, string $table): self {
        $qb = new self();
        $qb->database = $database;
        $qb->table = $table;

        return $qb;
    }

    public function where(string $column, mixed $value, string $operator = '='): self {
        $this->where[] = ['column' => $column, 'operator' => $operator, 'value' => $value];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->order[] = ['column' => $column, 'direction' => $direction];

        return $this;
    }

    public function limit(int $limit, int $offset = 0): self {
        $this->limit = max(0, $limit);
        $this->offset = max(0, $offset);

        return $this;
    }

    public function get(): array {
        $rows = GBDB::getData($this->database, $this->table) ?: [];
        $rows = array_values(array_filter($rows, fn($row) => is_array($row) && !(($row['id'] ?? null) === -1)));

        foreach ($this->where as $filter) {
            $rows = array_values(array_filter($rows, fn($row) => $this->match($row, $filter)));
        }

        foreach (array_reverse($this->order) as $order) {
            usort($rows, function ($a, $b) use ($order) {
                $left = $a[$order['column']] ?? null;
                $right = $b[$order['column']] ?? null;
                $cmp = $left <=> $right;

                return $order['direction'] === 'DESC' ? -$cmp : $cmp;
            });
        }

        if ($this->offset > 0 || $this->limit > 0) {
            $rows = array_slice($rows, $this->offset, $this->limit > 0 ? $this->limit : null);
        }

        return $rows;
    }

    public function first(): ?array {
        $oldLimit = $this->limit;
        $oldOffset = $this->offset;
        $this->limit(1);
        $rows = $this->get();
        $this->limit = $oldLimit;
        $this->offset = $oldOffset;

        return $rows[0] ?? null;
    }

    public function insert(array $row): int {
        return GBDB::insertData($this->database, $this->table, $row);
    }

    public function update(array $data): array {
        $rows = $this->get();
        $changed = 0;

        foreach ($rows as $row) {
            if (!isset($row['id'])) continue;

            if (GBDB::editData($this->database, $this->table, 'id', $row['id'], $data)) $changed++;
        }

        return ['ok' => true, 'changed' => $changed];
    }

    public function delete(): array {
        $rows = $this->get();
        $deleted = 0;

        foreach ($rows as $row) {
            if (!isset($row['id'])) continue;

            if (GBDB::deleteData($this->database, $this->table, 'id', $row['id'])) $deleted++;
        }

        return ['ok' => true, 'deleted' => $deleted];
    }

    private function match(array $row, array $filter): bool {
        $value = $row[$filter['column']] ?? null;
        $needle = $filter['value'];

        return match ($filter['operator']) {
            '!=', '<>' => $value != $needle,
            '>', 'gt' => is_numeric($value) && is_numeric($needle) && $value > $needle,
            '<', 'lt' => is_numeric($value) && is_numeric($needle) && $value < $needle,
            '>=', 'gte' => is_numeric($value) && is_numeric($needle) && $value >= $needle,
            '<=', 'lte' => is_numeric($value) && is_numeric($needle) && $value <= $needle,
            '~=', 'contains' => str_contains(mb_strtolower((string)$value), mb_strtolower((string)$needle)),
            default => $value == $needle,
        };
    }

}
