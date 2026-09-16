<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->bound('db') && ! app()->environment('testing_no_passport')) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--name' => 'Testing Personal Access Client',
                '--no-interaction' => true,
            ]);
        }
    }
}