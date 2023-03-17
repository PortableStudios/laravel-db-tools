<?php

namespace Portable\LaravelDbTools\Database\CliDrivers;

use Symfony\Component\Process\Process;

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


    protected function verifyProcess($proc)
    {
        $process = new Process([$proc, "--version"]);
        $exitCode = $process->run();
        if ($exitCode !== 0) {
            throw new \Exception("$proc is not installed");
        }
    }

    public function hasPV()
    {
        try {
            $this->verifyProcess('pv');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
