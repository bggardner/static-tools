<?php

namespace Bggardner\StaticTools\PDO\MySQL;

use \Bggardner\StaticTools\PDO\PDO;

class Select
{
    protected static ?int $count;
    protected static string $from = '';
    protected static string $where = '';
    protected static array $values = [];
    protected static string $group_by = '';
    protected static string $having = '';
    protected static string $order_by = '';
    protected static string $limit = '';

    public static function andWhere(string $expr, ?array $values = []): void
    {
        if (strlen(static::$where) == 0) {
            static::$where = 'WHERE 1';
        }
        static::$where .= ' AND (' . trim($expr) . ')';
        static::bind($values);
        static::$count = null;
    }

    protected static function bind(?array $values = []): void
    {
        static::$values = array_merge(static::$values, $values);
    }

    protected static function body(): string
    {
        return implode(' ', [
            static::$from,
            static::$where,
            static::$group_by,
            static::$having
        ]);
    }

    public static function count(): int
    {
        if (is_null(static::$count)) {

            if (static::$having == '') {
                $query = 'SELECT COUNT(*) ' . static::body();
            } else {
                $query = 'SELECT COUNT(*) FROM (SELECT NULL ' . static::body() . ') AS `subquery`';
            }

try {
            static::$count = PDO::execute(
                $query,
                static::$values
            )->fetchColumn();
} catch (\Exception $e) {
exit($e->getMessage() . ': ' . $query);
}
        }

        return static::$count;
    }

    public static function from(string $table_references): void
    {
        static::$from = 'FROM ' . trim($table_references);
        static::$count = null;
    }

    public static function groupBy(?string $expr = ''): void
    {
        if ($expr == '') {
            static::$group_by = '';
        } else {
            static::$group_by = 'GROUP BY ' . trim($expr);
        }

        static::$count = null;
    }

    public static function hasWhere(): bool
    {
        return strlen(static::$where) > 0;
    }

    public static function having(string $where_condition): void
    {
        if (strlen($where_condition)) {
            static::$having = 'HAVING ' . trim($where_condition);
            static::$count = null;
        }
    }

    public static function limit(int $limit, ?int $offset = 0): void
    {
        static::$limit = 'LIMIT ' . $offset . ', ' . $limit;
    }

    public static function orderBy(array $columns): void
    {
        if (count($columns)) {
            $sublclauses = [];

            foreach ($columns as $column => $direction) {
                $subclauses[] = '`' . $column . '` ' . ($direction ?? 'ASC');
            }

            static::$order_by = 'ORDER BY ' . implode(', ', $subclauses);
        }
    }

    public static function reset(): void
    {
        $reflection = new \ReflectionClass(get_called_class());
        $properties = $reflection->getDefaultProperties();

        foreach ($properties as $property => $default) {
            static::${$property} = $default;
        }
    }

    public static function select(string $select_expr): \PDOStatement
    {
        return PDO::execute(
            implode(' ', [
                'SELECT ' . trim($select_expr),
                static::body(),
                static::$order_by,
                static::$limit
            ]),
            static::$values
        );
    }
}
