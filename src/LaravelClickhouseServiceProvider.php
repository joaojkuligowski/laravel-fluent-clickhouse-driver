<?php

namespace JoaoJ\LaravelClickhouse;

use JoaoJ\LaravelClickhouse\LaravelClickhouseModel as Model;
use Illuminate\Support\ServiceProvider;

class LaravelClickhouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $defaultConfig = [
            'host' => '127.0.0.1',
            'mode' => 'tcp',
            'port' => 8123,
            'username' => 'default',
            'password' => '',
            'database' => 'default',
            'https' => false,
            'readonly' => false,
            'async' => false,
        ];

        $this->app->resolving('db', function ($db) use ($defaultConfig) {
            $db->extend('clickhouse', function ($config, $name) use ($defaultConfig) {
                $config = array_merge($defaultConfig, $config);
                $config['name'] = $name;
                return new LaravelClickhouseConnection($config);
            });
        });
    }

    public function boot(): void
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }
}
