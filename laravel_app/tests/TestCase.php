<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        if (in_array($connection, ['mysql', 'mariadb'], true) && ! str_ends_with($app['config']->get("database.connections.$connection.database"), '_test')) {
            throw new \RuntimeException('MySQL tests require a dedicated database ending in _test.');
        }
        return $app;
    }
}
