<?php

namespace Portable\LaravelDbTools\Console\Commands;

use Illuminate\Console\Command;
use Portable\LaravelDbTools\Database\CliFactory;
use Illuminate\Support\Facades\DB;
use Portable\LaravelDbTools\PassthruProcess;
use Symfony\Component\Process\Process;

class DbExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {filename?} {connection?} {--command-only} {--compress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the database to a file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection = $this->argument('connection');
        $fileName = $this->argument('filename') ?: DB::connection($connection)->getDatabaseName() . '_export_' . date('Y-m-d-H-i-s') . '.sql';
        $cliDriver = CliFactory::make(DB::connection($connection));
        $compress = $this->option('compress');

        $command = $cliDriver->getDumpCommand($fileName, $compress);
        if ($this->option('command-only')) {
            $this->comment("Command is: $command");
            $exit = 0;
        } else {
            $process = PassthruProcess::fromShellCommandline($command);
            $exit = $process->run();

            if ($exit == 0) {
                $this->comment("Database exported to $fileName");
            }
        }
    }
}
