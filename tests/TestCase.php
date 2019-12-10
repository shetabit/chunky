<?php

namespace Shetabit\Chunky\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return ['Shetabit\Chunky\Provider\ChunkyServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Chunky' => 'Shetabit\Chunky\Facade\Chunky',
        ];
    }
}
