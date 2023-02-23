<?php

namespace Portable\LaravelDbTools\Database\CliDrivers;

use Symfony\Component\Process\Process;

class PostgresCli extends AbstractCliDriver
{
    public function getDropDbCommand()
    {
        $database = $this->connection->getDatabaseName();
        $username = $this->connection->getConfig('username');
        $password = $this->connection->getConfig('password');
        $host = $this->connection->getConfig('host');
        $port = $this->connection->getConfig('port');

        return "PGPASSWORD={$password} dropdb --if-exists --username={$username} --host={$host} --port={$port} {$database}";
    }

    public function getCreateDbCommand()
    {
        $database = $this->connection->getDatabaseName();
        $username = $this->connection->getConfig('username');
        $password = $this->connection->getConfig('password');
        $host = $this->connection->getConfig('host');
        $port = $this->connection->getConfig('port');

        return "PGPASSWORD={$password} createdb --username={$username} --host={$host} --port={$port} {$database}";
    }

    public function getDumpCommand($destination, $compress = false)
    {
        $config = $this->connection->getConfig();
        $this->verifyRequiredTools($compress);

        $database = $this->connection->getDatabaseName();

        $sprintString = 'PGPASSWORD=%s pg_dump -U %s -h %s -p %s %s';

        if ($this->hasPV()) {
            $pSQLCmd = "PGPASSWORD=%s psql -U %s -h %s -p %s %s -tc \"SELECT pg_database_size('$database')\"";
            $pSQLCmd = sprintf($pSQLCmd, $config['password'], $config['username'], $config['host'], $config['port'], $database);

            $sprintString .= ' | pv -c -s $(' . $pSQLCmd . ') -N dump';
        }

        if ($compress) {
            $sprintString .= ' | gzip';
        }
        $sprintString .= ' > %s';

        return sprintf(
            $sprintString,
            $config['password'],
            $config['username'],
            $config['host'],
            $config['port'],
            $database,
            $destination
        );
    }

    public function getImportCommand($source, $compressed = false)
    {
        $this->verifyRequiredTools($compressed);

        $hasPV = $this->hasPV();

        $database = $this->connection->getDatabaseName();
        $config = $this->connection->getConfig();
        $outputFile = $source . '.log';

        $sprintString = "";
        $pSQL = 'PGPASSWORD=%s psql -U %s -h %s -p %s %s -o=%s -q -v ON_ERROR_STOP=1';

        if ($hasPV) {
            $sprintString .= 'pv %s | ';
            if ($compressed) {
                $sprintString .= 'gunzip |';
            }
            $sprintString .= $pSQL;
        } else {
            if ($compressed) {
                $sprintString .= 'gunzip < %s | ' . $pSQL;
            } else {
                $sprintString .= 'cat %s | ' . $pSQL;
            }
        }


        return sprintf(
            $sprintString,
            $source,
            $config['password'],
            $config['username'],
            $config['host'],
            $config['port'],
            $database,
            $outputFile
        );
    }

    public function verifyRequiredTools($compression = false)
    {
        $procs = ['psql', 'pg_dump', 'createdb', 'dropdb'];
        if ($compression) {
            $procs[] = 'gzip';
            $procs[] = 'gunzip';
        }

        foreach ($procs as $proc) {
            $this->verifyProcess($proc);
        }

        return true;
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

    protected function verifyProcess($proc)
    {
        $process = new Process([$proc, "--version"]);
        $exitCode = $process->run();
        if ($exitCode !== 0) {
            throw new \Exception("$proc is not installed");
        }
    }
}
