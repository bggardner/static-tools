<?php

namespace Bggardner\StaticTools\PDO\MySQL\Traits;

trait TreeTrait
{
    /**
     * Adds a new node as the left-most child of a parent in a tree
     *
     * @param string $table Name of the database table
     * @param string $name Name of the new node
     * @param int $parent ID of the parent
     * @return int ID of the added node
     */
    public static function addTreeNode(string $table, string $name, int $parent): int
    {
        static::checkDuplicateTreeNode($table, $name, $parent);
        $stmt  = static::execute('
  LOCK TABLES `' . $table . '` WRITE;
  SELECT @newLeft := `right` FROM `' . $table . '` WHERE `id` = ?;
  UPDATE `' . $table . '` SET `right` = `right` + 2 WHERE `right` >= @newLeft;
  UPDATE `' . $table . '` SET `left` = `left` + 2 WHERE `left` > @newLeft;
  INSERT INTO `' . $table . '` (`name`,`left`,`right`) VALUES (?, @newLeft, @newLeft + 1);
  UNLOCK TABLES;',
            [
                ['value' => $parent, 'type' => \PDO::PARAM_INT],
                ['value' => $name, 'type' => \PDO::PARAM_STR]
            ]
        );
        return static::lastInsertId();
    }

    /**
     * Add a new tree table
     *
     * @param string $table Name of the database table
     * @param string $root Name of the root node
     * @param string $table_options MySQL table options
     */
    public static function addTree(string $table, string $root, string $table_options = ''): void
    {
        static::exec('
  CREATE TABLE IF NOT EXISTS `' . $table . '` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `left` INT UNSIGNED NOT NULL,
    `right` INT UNSIGNED NOT NULL
  )
  ' . $table_options . ';
  INSERT INTO `' . $table . '` (`id`, `name`, `left`, `right`)
  VALUES (1, ' . static::quote($root) . ', 1, 2)
  ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
        ');
    }

    /**
     * Checks if the tree has a sibling with the same name
     *
     * @param string $table Name of the database table
     * @param string $name Name of the new node
     * @param int $parent ID of the parent
     * @param int $node ID of the node
     */
    protected static function checkDuplicateTreeNode(string $table, string $name, int $parent, int $node = 0): void
    {
/*
        $stmt = static::execute('
  SELECT
    1
  FROM `' . $table . '` AS `nodes`
  CROSS JOIN `' . $table . '` AS `parents`
  WHERE
    `nodes`.`left` BETWEEN `parents`.`left` AND `parents`.`right`
    AND `parents`.`id` = ?
    AND `nodes`.`name` = ?
    AND `nodes`.`id` != ?',
            [
                ['value' => $parent, 'type' => \PDO::PARAM_INT],
                ['value' => $name, 'type' => \PDO::PARAM_STR],
                ['value' => $node, 'type' => \PDO::PARAM_INT]
            ]
        )->fetchColumn();
*/
        $sql_mode = static::query('SELECT @@sql_mode')->fetchColumn();
        static::exec('SET sql_mode=' . static::quote(implode(',', array_diff(explode(',', $sql_mode), ['ONLY_FULL_GROUP_BY']))));

        try {
            $stmt = static::execute('
  SELECT
    (COUNT(`parents`.`id`) - (`sub_tree`.`depth` + 1)) AS `depth`
  FROM
    `' . $table . '` AS `nodes`,
    `' . $table . '` AS `parents`,
    `' . $table . '` AS `sub_parents`,
    (
      SELECT
        `nodes`.`id`,
        (COUNT(`parents`.`id`) - 1) AS `depth`
      FROM
        `' . $table . '` AS `nodes`,
        `' . $table . '` AS `parents`
      WHERE
        `nodes`.`left` BETWEEN `parents`.`left` AND `parents`.`right`
        AND `nodes`.`id` = ?
      GROUP BY `nodes`.`id`
      ORDER BY `nodes`.`left`
    ) AS `sub_tree`
  WHERE
    `nodes`.`left` BETWEEN `parents`.`left` AND `parents`.`right`
    AND `nodes`.`left` BETWEEN `sub_parents`.`left` AND `sub_parents`.`right`
    AND `sub_parents`.`id` = `sub_tree`.`id`
    AND `nodes`.`name` = ?
    AND `nodes`.`id` != ?
  GROUP BY `nodes`.`id`
  HAVING `depth` = 1
  ORDER BY `nodes`.`left`;
                ',
                [
                    ['value' => $parent, 'type' => \PDO::PARAM_INT],
                    ['value' => $name, 'type' => \PDO::PARAM_STR],
                    ['value' => $node, 'type' => \PDO::PARAM_INT]
                ]
            )->fetchColumn();
        } catch (\Exception $e) {
            static::exec('SET sql_mode=' . static::quote($sql_mode));
            throw $e;
        }

        static::exec('SET sql_mode=' . static::quote($sql_mode));

        if ($stmt) {
            throw new \Exception('Tree cannot have a sibling with the same name');
        }
    }

    /**
     * Deletes a node from the tree
     *
     * @param string $table Name of the database table
     * @param int $id ID of the node
     */
    public static function deleteTreeNode(string $table, int $id): void
    {
        static::execute('
  LOCK TABLES `' . $table . '` WRITE;
  SELECT @myLeft := `left`, @myRight := `right`, @myWidth := `right` - `left` + 1 FROM `' . $table . '` WHERE `id` = ?;
  DELETE FROM `' . $table . '` WHERE `left` BETWEEN @myLeft AND @myRight;
  UPDATE `' . $table . '` SET `right` = `right` - @myWidth WHERE `right` > @myRight;
  UPDATE `' . $table . '` SET `left` = `left` - @myWidth WHERE `left` > @myRight;
  UNLOCK TABLES;',
            [['value' => $id, 'type' => \PDO::PARAM_INT]]
        );
    }

    /**
     * Edits the node name and optionally moves the node (and its children) to a new parent
     *
     * @param string $table Name of the database table
     * @param int $id ID of the node
     * @param string $name New name for the node
     * @param int|null $parent ID of the parent if node is to be moved
     */
    public static function editTreeNode(string $table, int $id, string $name, int|null $parent = null): void
    {
        static::checkDuplicateTreeNode($table, $name, $parent, $id);
        $stmt = static::execute(
            'UPDATE `' . $table . '` SET `name` = ? WHERE `id` = ?',
            [
                ['value' => $name, 'type' => \PDO::PARAM_STR],
                ['value' => $id, 'type' => \PDO::PARAM_INT]
            ]
        );
        if (!is_null($parent)) {
            static::moveTreeNode($table, $id, $parent);
        }
    }

    /**
     * Moves a node and its children as the left-most child of a parent
     *
     * @param string $table Name of the database table
     * @param int $id ID of the node to be moved
     * @param int $parent ID of the parent
     */
    public static function moveTreeNode(string $table, int $id, int $parent): void
    {
        $invalid = static::execute('
  SELECT
    (SELECT `left` FROM `' . $table . '` WHERE `id` = ?) BETWEEN
      (SELECT `left` FROM `' . $table . '` WHERE `id` = ?)
      AND
      (SELECT `right` FROM `' . $table . '` WHERE `id` = ?)',
            [
                ['value' => $parent, 'type' => \PDO::PARAM_INT],
                ['value' => $id, 'type' => \PDO::PARAM_INT],
                ['value' => $id, 'type' => \PDO::PARAM_INT]
            ]
        )->fetchColumn();
        if ($invalid) {
            throw new \Exception('Move failed: target parent must be ancestor.');
        }
        $stmt = static::execute('
  LOCK TABLES `' . $table . '` WRITE;
  SELECT @nodeLeft := `left`, @nodeRight := `right`, @nodeSize := `right` - `left` + 1 FROM `' . $table . '` WHERE `id` = ?;
  SELECT @maxRight := MAX(`right`) FROM `' . $table . '`;
  UPDATE `' . $table . '` SET `left` = `left` + @maxRight, `right` = `right` + @maxRight WHERE `left` BETWEEN @nodeLeft AND @nodeRight; # Shift sub-tree above @maxRight
  UPDATE `' . $table . '` SET `right` = `right` - @nodeSize  WHERE `right` BETWEEN @nodeRight AND @maxRight; # Same as deleting
  UPDATE `' . $table . '` SET `left` = `left` - @nodeSize  WHERE `left` BETWEEN @nodeRight AND @maxRight; # Same as deleting
  SELECT @parentLeft := `left`, @parentRight := `right` FROM `' . $table . '` WHERE `id` = ?;
  UPDATE `' . $table . '` SET `right` = `right` + @nodeSize WHERE `right` >= @parentLeft AND `right` <= @maxRight; # Same as adding
  UPDATE `' . $table . '` SET `left` = `left` + @nodeSize WHERE `left` > @parentLeft AND `left` <= @maxRight; # Same as adding
  UPDATE `' . $table . '` SET `left` = `left` - @maxRight - @nodeLeft + @parentLeft + 1, `right` = `right` - @maxRight - @nodeLeft + @parentLeft + 1 WHERE `left` > @maxRight;
  UNLOCK TABLES;',
            [
                ['value' => $id, 'type' => \PDO::PARAM_INT],
                ['value' => $parent, 'type' => \PDO::PARAM_INT]
            ]
        );
    }
}
