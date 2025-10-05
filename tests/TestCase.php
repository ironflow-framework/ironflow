<?php

declare(strict_types=1);

namespace IronFlow\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use IronFlow\IronFlowServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            IronFlowServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('ironflow.auto_discover', false);
        config()->set('ironflow.auto_boot', false);
    }
}
