<?php

namespace Bggardner\StaticTools\PDO;

/**
 * This class provides a statically accessible PDO connection
 */
class PDO
{
    /**
     * Internal PDO instance
     */
    protected static $pdo;

    /**
     * Any method not defined is passed to the static PDO
     */
    public static function __callStatic($name, $arguments)
    {
        if (!is_a(static::$pdo, '\PDO')) {
            throw new \Exception(static::class . '::connect must be called before calling ' . $name . '()');
        }
        return call_user_func_array([static::$pdo, $name], $arguments);
    }

    /**
     * @see \PDO::__construct()
     */
    public static function connect(string $dsn, ?string $username = null, ?string $password, ?array $options = null): void
    {
        static::$pdo = new \PDO($dsn, $username, $password, $options);
    }

    /**
     * Prepares a statement, binds vales, and executes
     *
     * @param string $query @see \PDO::prepare()
     * @param array $params Multi-dimensional array of parameters to bind:
     *                      Keys indicate parameter identifier: $param parameter of {@see \PDOStatement::bindValue()}
     *                          Numeric keys are 0-indexed unlike bindValue()
     *                      Values are arrays with keys:
     *                          'value': $value parameter of {@see \PDOStatement::bindValue()}
     @                          'type': $type parameter of {@see \PDOStatement::bindValue()}
     * @param array $options {@see \PDO::prepare()}
     */
    public static function execute($query, $params = [], $options = [])
    {
        $stmt = static::prepare($query, $options);
        foreach ($params as $key => $param) {
            $stmt->bindValue(is_int($key) ? $key + 1 : $key, $param['value'], $param['type']);
        }
        $stmt->execute();
        return $stmt;
    }
}
