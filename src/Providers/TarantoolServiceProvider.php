<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Providers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelTarantool\Database\Connection;

class TarantoolServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('tarantool', fn($config, $name) => new Connection($config));
        });
    }
}
