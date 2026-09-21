<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->bound('db') && Schema::hasTable('oauth_clients')) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--name' => 'Testing Personal Access Client',
                '--no-interaction' => true,
            ]);
        }
    }
}