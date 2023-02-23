<?php

namespace Portable\LaravelDbTools;

use PDO;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Pipes\UnixPipes;
use Symfony\Component\Process\Pipes\WindowsPipes;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;

class PassthruProcess
{

    protected $commandLine;

    public static function fromShellCommandline($commandLine)
    {
        $item = new static();
        $item->commandLine = $commandLine;
        return $item;
    }
    public function run()
    {
        passthru($this->commandLine, $exit);
        return $exit;
    }
}
