<?php

namespace Portable\LaravelDbTools\Database\CliDrivers;

abstract class AbstractCliDriver
{
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    abstract public function getDropDbCommand();
    abstract public function getCreateDbCommand();
    abstract public function getDumpCommand($destination, $compress = false);
    abstract public function getImportCommand($source, $compressed = false);
    abstract public function verifyRequiredTools($compression = false);
}
