<?php

namespace Portable\LaravelDbTools\Console\Commands;

use Illuminate\Console\Command;
use Portable\LaravelDbTools\Database\CliFactory;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\App;
use Portable\LaravelDbTools\PassthruProcess;

class DbImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import {file} {connection?} {--command-only} {--compressed} {--drop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the database from a file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $fileName = $this->argument('file');
        $connection = $this->argument('connection');
        $compressed = $this->option('compressed');

        $cliDriver = CliFactory::make(DB::connection($connection));

        if (App::environment('production')) {
            $this->error('=========================================================');
            $this->error('==               Production Environment                ==');
            $this->error('=========================================================');
            $confirm = $this->ask("Are you sure you want to continue (y/n)?", 'n');
            if ($confirm !== 'y') {
                $this->comment("Aborting import");
                return;
            }
        }

        $importCommand = $cliDriver->getImportCommand($fileName, $compressed);
        if ($this->option('drop')) {
            $this->error('=========================================================');
            $this->error('==           DROP DATABASE!  Are you sure?             ==');
            $this->error('=========================================================');
            $confirm = $this->ask("Are you sure you want to continue (y/n)?", 'n');
            if ($confirm !== 'y') {
                $this->comment("Aborting import");
                return;
            }

            $command = $cliDriver->getDropDbCommand() . ' && ' . $cliDriver->getCreateDbCommand() . ' && ' . $importCommand;
        } else {
            $command = $importCommand;
        }

        if ($this->option('command-only')) {
            $this->comment("Command is: $command");
            $exit = 0;
        } else {
            DB::disconnect($connection);
            if ($this->option('drop')) {
                PassthruProcess::fromShellCommandline($cliDriver->getDropDbCommand())->run();
                PassthruProcess::fromShellCommandline($cliDriver->getCreateDbCommand())->run();
            }
            $process = PassthruProcess::fromShellCommandline($importCommand);
            $exit = $process->run();
            DB::connection($connection);
            if ($exit == 0) {
                $this->comment("Database imported from $fileName");
            }
        }
    }
}
