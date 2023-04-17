<?php

namespace Bggardner\StaticTools\PDO;

abstract class AbstractSessionHandler implements \SessionHandlerInterface
{
    protected $table;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->install();
    }

    abstract protected function install();
    abstract protected function uninstall();
}
