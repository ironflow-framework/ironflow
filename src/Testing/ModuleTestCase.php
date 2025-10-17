<?php

use IronFlow\Tests\TestCase;

abstract class ModuleTestCase extends TestCase
{
    /**
     * The module to test.
     *
     * @var string
     */
    protected string $moduleName;

    /**
     * Set up the module for testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleName = 'IronFlow';
        $this->app['config']->set('modules.enabled', true);
        $this->app['config']->set('modules.modules', [$this->moduleName => []]);
    }
}
