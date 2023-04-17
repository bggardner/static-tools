<?php

namespace Bggardner\StaticTools\PDO\MySQL;

use Bggardner\StaticTools\PDO\PDO;

class SessionHandler extends \Bggardner\StaticTools\PDO\AbstractSessionHandler
{
    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = PDO::prepare('DELETE FROM `' . $this->table . '` WHERE `id` = ?');
            $stmt->bindValue(1, $id, \PDO::PARAM_STR);
            $stmt->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = PDO::prepare('DELETE FROM `' . $this->table . '` WHERE `created` < ?');
            $stmt->bindValue(1, time() - $max_lifetime, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = PDO::prepare('SELECT `data` FROM `' . $this->table . '` WHERE `id` = ?');
            $stmt->bindValue(1, $id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn() ?: '';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = PDO::prepare('
  INSERT
    INTO `' . $this->table . '`
    (`id`, `created`, `data`)
    VALUES (:id, :created, :data)
    ON DUPLICATE KEY UPDATE `created` = :created, `data` = :data
            ');
            $stmt->bindValue(':id', $id, \PDO::PARAM_STR);
            $stmt->bindValue(':created', time(), \PDO::PARAM_INT);
            $stmt->bindValue(':data', $data, \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function install(): void
    {
        PDO::exec('
  CREATE TABLE IF NOT EXISTS `' . $this->table . '` (
    `id` VARBINARY(' . ini_get('session.sid_length') . ') NOT NULL PRIMARY KEY,
    `created` INT UNSIGNED NOT NULL DEFAULT 0,
    `data` BLOB NOT NULL
  ) COLLATE utf8mb4_bin, ENGINE InnoDB
        ');
    }

    public function uninstall(): void
    {
        PDO::exec('DROP TABLE `' . $this->$table . '`');
    }
}
