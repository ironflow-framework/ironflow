<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;
use IronFlow\Publishing\ModulePublisher;
use IronFlow\Contracts\ExportableInterface;
use IronFlow\Core\BaseModule;

/**
 * PublishModuleCommand
 *
 * Publish a module as a standalone Packagist package.
 */
class PublishModuleCommand extends Command
{
    protected $signature = 'ironflow:publish {module : The module name to publish}
                            {--init : Initialize git repository}
                            {--tag : Create initial git tag}
                            {--push : Push to remote repository}';
    
    protected $description = 'Prepare and publish a module as a standalone package';

    public function handle(ModulePublisher $publisher): int
    {
        $moduleName = $this->argument('module');
        $module = Anvil::getModule($moduleName);

        if (!$module) {
            $this->error("Module '{$moduleName}' not found");
            return self::FAILURE;
        }

        if (!$module instanceof ExportableInterface) {
            $this->error("Module '{$moduleName}' must implement ExportableInterface");
            $this->info("Add 'implements ExportableInterface' to your module class");
            return self::FAILURE;
        }

        $this->info("Publishing module: {$moduleName}");
        $this->newLine();

        try {
            $packagePath = $publisher->publish($module);
            
            $this->info("✓ Module files copied");
            $this->info("✓ composer.json generated");
            $this->info("✓ README.md generated");
            $this->info("✓ LICENSE generated");
            $this->info("✓ Tests skeleton created");
            
            $this->newLine();
            $this->info("Package created at: {$packagePath}");

            // Initialize git repository
            if ($this->option('init')) {
                $this->initGitRepository($packagePath, $module);
            }

            $this->newLine();
            $this->info("Next steps:");
            $this->line("  1. Review generated files in: {$packagePath}");
            $this->line("  2. Run: cd {$packagePath} && composer install");
            $this->line("  3. Run tests: composer test");
            $this->line("  4. Create repository on GitHub");
            $this->line("  5. Push: git remote add origin <your-repo-url> && git push -u origin main");
            $this->line("  6. Submit to Packagist: https://packagist.org/packages/submit");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Publishing failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    protected function initGitRepository(string $packagePath, BaseModule $module): void
    {
        $this->info("Initializing git repository...");

        chdir($packagePath);

        exec('git init');
        exec('git add .');
        exec('git commit -m "Initial commit"');
        
        $this->info("✓ Git repository initialized");

        if ($this->option('tag')) {
            $version = $module->getMetadata()->version;
            exec("git tag v{$version}");
            $this->info("✓ Created tag v{$version}");
        }

        if ($this->option('push') && $this->option('tag')) {
            exec('git push origin main --tags');
            $this->info("✓ Pushed to remote");
        }
    }
}