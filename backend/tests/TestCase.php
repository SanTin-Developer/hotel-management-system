<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->app = $this->createApplication();

        config([
            'database.connections.pgsql.host' => env('DB_HOST', config('database.connections.pgsql.host')),
            'database.connections.pgsql.port' => env('DB_PORT', config('database.connections.pgsql.port')),
            'database.connections.pgsql.database' => env('DB_TEST_DATABASE', 'hotel_management_test'),
            'database.connections.pgsql.username' => env('DB_USERNAME', config('database.connections.pgsql.username')),
            'database.connections.pgsql.password' => env('DB_PASSWORD', config('database.connections.pgsql.password')),
        ]);

        DB::purge('pgsql');

        parent::setUp();
    }
}