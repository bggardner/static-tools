<?php

namespace Bggardner\StaticTools\PDO\MySQL\Traits;

trait EnumTrait
{
    public static function addEnum(string $enum, string $table, string $table_options = ''): string
    {
        $reflection = new \ReflectionEnum($enum);
        $backing_type = $reflection->getBackingType();
        $is_int = $backing_type ? ($backing_type->getName() == 'int') : false;
        $values = [];
        $name_size = 0;
        $ints = [];
        $varchar_size = 0;
        $cases = $enum::cases();
        foreach ($cases as $case) {
            $value = '(' . static::quote($case->name);
            $name_size = max($name_size, strlen($case->name));
            if ($reflection->isBacked()) {
                $value .= ', ';
                if ($is_int) {
                    $value .= $case->value;
                    $ints[] = $case->value;
                } else { // Must be a string
                    $value .= static::quote($case->value);
                    $varchar_size = max($value_size, strlen($case->value));
                }
            }
            $value .= ')';
            $values[] = $value;
        }
        $values = implode(",\n", $values);

        $table = '`' . static::$table_prefix . $table . '`';
        $sql = '
  CREATE TABLE IF NOT EXISTS ' . $table . ' (
    `name` VARCHAR(' . $name_size . ') NOT NULL ';
        if ($reflection->isBacked()) {
            $sql .= 'UNIQUE,
    `value` ';
            if ($is_int) {
                $max = max($ints);
                $min = min($ints);
                if ($min < 0) {
                    if ($min < -2147483648 || $max > 2147483647) {
                        $sql .= 'BIGINT';
                    } else if ($min < -8388608 || $max > 8388607) {
                        $sql .= 'INT';
                    } else if ($min < -32768 || $max > 32767) {
                        $sql .= 'MEDIUMINT';
                    } else if ($min < -128 || $max > 127) {
                        $sql .= 'SMALLINT';
                    } else {
                        $sql .= 'INT';
                    }
                } else {
                    if ($max > 4294967295) {
                        $sql .= 'BIGINT';
                    } else if ($max > 16777215) {
                        $sql .= 'INT';
                    } else if ($max > 65535) {
                        $sql .= 'MEDIUMINT';
                    } else if ($max > 255) {
                        $sql .= 'SMALLINT';
                    } else {
                        $sql .= 'TINYINT';
                    }
                    $sql .= ' UNSIGNED';
                }
            } else {
                $sql .= 'VARCHAR(' . $varchar_size . ')';
            }
            $sql .= ' NOT NULL PRIMARY KEY';
        } else {
            $sql .= 'PRIMARY KEY';
        }
        $sql .= '
  )
  ' . $table_options . ';';
        $sql .= '
  INSERT INTO ' . $table . ' (`name`' . ($reflection->isBacked() ? ', `value`' : ''). ')
  VALUES
    ' . $values . '
  ON DUPLICATE KEY UPDATE ';
        if ($reflection->isBacked()) {
            $sql .= '`name` = VALUES(`name`)';
        } else {
            $sql .= '`value` = VALUES(`value`)';
        }
        return static::exec($sql);
    }
}
