<?php

namespace Portable\LaravelDbTools\Database;

use Closure;
use Portable\LaravelDbTools\Database\CliDrivers\MysqlCli;
use Portable\LaravelDbTools\Database\CliDrivers\PostgresCli;
use Portable\LaravelDbTools\Database\CliDrivers\TestDriver;

class CliFactory
{
    /**
     * The cli resolvers.
     *
     * @var array
     */
    protected static $resolvers = [];

    /**
     * Register a cli resolver.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolverFor($driver, Closure $callback)
    {
        static::$resolvers[$driver] = $callback;
    }

    /**
     * Get the cli resolver for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return static::$resolvers[$driver] ?? null;
    }

    public static function make($connection)
    {
        $driver = $connection->getConfig('driver');
        return static::createConnection($driver, $connection);
    }

    public static function createConnection($driver, $connection)
    {
        if ($resolver = static::getResolver($driver)) {
            return $resolver($connection);
        }

        switch ($driver) {
            case 'pgsql':
                return new PostgresCli($connection);
            case 'mysql':
                return new MysqlCli($connection);
        }

        throw new \Exception("Unsupported driver [{$driver}].");
    }
}
