<?php

declare(strict_types=1);

/**
 * ModuleSeeder
 *
 * Base seeder for modules.
 */
namespace IronFlow\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

abstract class ModuleSeeder extends Seeder
{
    /**
     * Module name.
     *
     * @var string
     */
    protected string $moduleName;

    /**
     * Seeder path.
     *
     * @var string
     */
    protected string $seederPath;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Call another seeder.
     *
     * @param string $class
     * @return void
     */
    protected function callModuleSeeder(string $class): void
    {
        $fullClass = $this->getSeederClass($class);

        if (class_exists($fullClass)) {
            $this->call($fullClass);
        }
    }

    /**
     * Get full seeder class name.
     *
     * @param string $class
     * @return string
     */
    protected function getSeederClass(string $class): string
    {
        $namespace = config('ironflow.namespace', 'Modules');
        return "{$namespace}\\{$this->moduleName}\\Database\\Seeders\\{$class}";
    }
}
