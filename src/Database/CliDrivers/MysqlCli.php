<?php

namespace Portable\LaravelDbTools\Database\CliDrivers;

use Illuminate\Support\Facades\DB;

class MysqlCli extends AbstractCliDriver
{
    public function getDropDbCommand()
    {
        $database = $this->connection->getDatabaseName();
        $username = $this->connection->getConfig('username');
        $password = $this->connection->getConfig('password');
        $host = $this->connection->getConfig('host');
        $port = $this->connection->getConfig('port');

        return "mysql -u{$username} -p{$password} -h{$host} -P{$port} -e \"DROP IF EXISTS DATABASE {$database};\"";
    }

    public function getCreateDbCommand()
    {
        $database = $this->connection->getDatabaseName();
        $username = $this->connection->getConfig('username');
        $password = $this->connection->getConfig('password');
        $host = $this->connection->getConfig('host');
        $port = $this->connection->getConfig('port');

        return "mysql -u{$username} -p{$password} -h{$host} -P{$port} -e \"CREATE DATABASE {$database};\"";
    }

    public function getDumpCommand($destination, $compress = false)
    {
        $config = $this->connection->getConfig();
        $database = $this->connection->getDatabaseName();

        $this->verifyRequiredTools($compress);

        $dumpCommand = sprintf("mysqldump -u%s -p%s -h%s -P%s %s", [
            $config['username'],
            $config['password'],
            $config['host'],
            $config['port'],
            $database,
        ]);

        if ($this->hasPV()) {
            $sql = "SELECT ROUND(SUM(data_length) / 1024 / 1024, 0) AS db_mb FROM information_schema.TABLES WHERE table_schema='"
                . DB::Connection()->getDatabaseName() . "'";

            $size = DB::select(DB::raw($sql))[0]->db_mb;

            $dumpCommand  .= " | pv --progress --size {$size}m ";
        }

        if ($compress) {
            $dumpCommand .= ' | gzip';
        }
        $dumpCommand .= ' > ' . $destination;
    }

    public function getImportCommand($source, $compressed = false)
    {
        $this->verifyRequiredTools($compressed);
        $hasPV = $this->hasPV();
        $database = $this->connection->getDatabaseName();
        $config = $this->connection->getConfig();
        $outputFile = $source . '.log';

        $importCommand = sprintf("mysql -u%s -p%s -h%s -P%s %s", [
            $config['username'],
            $config['password'],
            $config['host'],
            $config['port'],
            $database,
        ]);
        $command = '';

        if ($hasPV) {
            $command .= 'pv ' . $outputFile . ' | ';
            if ($compressed) {
                $command .= 'gunzip |';
            }
            $command .= $importCommand;
        } else {
            if ($compressed) {
                $command .= 'gunzip < ' . $outputFile . ' | ' . $importCommand;
            } else {
                $command = $importCommand . ' < ' . $outputFile;
            }
        }

        return $command;
    }

    public function verifyRequiredTools($compression = false)
    {
        $procs = ['mysql', 'mysqldump'];
        if ($compression) {
            $procs[] = 'gzip';
            $procs[] = 'gunzip';
        }

        foreach ($procs as $proc) {
            $this->verifyProcess($proc);
        }

        return true;
    }
}
