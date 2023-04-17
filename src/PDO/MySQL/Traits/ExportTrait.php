<?php

namespace Bggardner\StaticTools\PDO\MySQL\Traits;

trait ExportTrait
{
    /**
     * Returns simplified SQL with table structure and data
     *
     * @param ?array $tables Array of tables to export or if null, all in current database
     */
    public static function export(?array $tables = null): string
    {
        if (is_null($tables)) {
            $tables = static::query(
                'SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE()'
            )->fetchAll(\PDO::FETCH_COLUMN, 0);
        }
        $sql = '';
        foreach ($tables as $table) {
            $sql .= str_replace(
                'CREATE TABLE',
                'CREATE TABLE IF NOT EXISTS',
                static::execute('SHOW CREATE TABLE `' . $table . '`')->fetchColumn(1)
            ) . ";\n\n";
        }
        foreach ($tables as $table) {
            $table = '`' . $table . '`';
            $columns = static::execute('SHOW COLUMNS FROM ' . $table)->fetchAll();
            $temp = "INSERT\n  INTO " . $table . "\n  (";
            $temp .= implode(
                ', ',
                array_map(
                    function($column) { return '`' . $column['Field'] . '`'; },
                    $columns
                )
            );
            $temp .= ")\n  VALUES\n";
            $stmt = static::execute('SELECT * FROM ' . $table);
            $i = 0;
            while ($row = $stmt->fetch()) {
                $values = [];
                foreach ($columns as $column) {
                    $value = $row[$column['Field']];
                    if (!preg_match('/^(tinyint|smallint|mediumint|int|bigint)/i', $column['Type'])) {
                        $value = '"' . $value . '"';
                    }
                    $values[] = $value;
                }
                $temp .= $i++ ? ",\n" : '';
                $temp .= '    (' . implode(', ', $values) . ")";
            }
            if ($i) {
                $sql .= $temp . "\n  ON DUPLICATE KEY UPDATE ";
                $sql .= implode(
                    ", ",
                    array_map(
                        function($column) {
                            return '`' . $column['Field'] . '`=VALUES(`' . $column['Field'] . '`)';
                        },
                        $columns
                    )
                ) . ";\n\n";
            }
        }
        return $sql;
    }
}
