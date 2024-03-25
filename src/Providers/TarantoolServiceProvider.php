<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Providers;

use Illuminate\Support\ServiceProvider;
use Team64j\LaravelTarantool\Database\Connection;
use Team64j\LaravelTarantool\Models\Model;

class TarantoolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving('db', function ($db) {
            $db->extend('tarantool', function ($config, $name) {
                $config['name'] = $name;

                return new Connection($config);
            });
        });
    }

//    public function boot(): void
//    {
//        Model::setConnectionResolver($this->app->get('db'));
//        Model::setEventDispatcher($this->app->get('events'));
//    }
}
