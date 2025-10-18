<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;
use IronFlow\Versioning\VersionManager;
use Illuminate\Support\Facades\File;

class VersionBumpCommand extends Command
{
    protected $signature = 'ironflow:version:bump {module : Module name}
                            {type : Bump type (major, minor, patch)}
                            {--tag : Create git tag}
                            {--commit : Commit the change}';

    protected $description = 'Bump module version';

    public function handle(VersionManager $versionManager): int
    {
        $moduleName = $this->argument('module');
        $bumpType = $this->argument('type');

        if (!in_array($bumpType, ['major', 'minor', 'patch'])) {
            $this->error("Invalid bump type. Use: major, minor, or patch");
            return self::FAILURE;
        }

        $module = Anvil::getModule($moduleName);

        if (!$module) {
            $this->error("Module '{$moduleName}' not found.");
            return self::FAILURE;
        }

        $currentVersion = $module->getMetadata()->version;
        $newVersion = $versionManager->bump($currentVersion, $bumpType);

        $this->info("Current version: {$currentVersion}");
        $this->info("New version: {$newVersion}");
        $this->newLine();

        if (!$this->confirm('Proceed with version bump?', true)) {
            return self::SUCCESS;
        }

        // Update version in module file
        $modulePath = $module->getPath();
        $moduleFile = $modulePath . '/' . $moduleName . 'Module.php';

        if (!File::exists($moduleFile)) {
            $this->error("Module file not found: {$moduleFile}");
            return self::FAILURE;
        }

        $content = File::get($moduleFile);
        $content = preg_replace(
            "/version:\s*['\"]" . preg_quote($currentVersion, '/') . "['\"]/",
            "version: '{$newVersion}'",
            $content
        );

        File::put($moduleFile, $content);

        $this->info("Version updated in {$moduleFile}");

        // Git operations
        if ($this->option('commit')) {
            exec("cd {$modulePath} && git add {$moduleFile}");
            exec("cd {$modulePath} && git commit -m 'chore: bump version to {$newVersion}'");
            $this->info("Changes committed");
        }

        if ($this->option('tag')) {
            exec("cd {$modulePath} && git tag v{$newVersion}");
            $this->info("Tag v{$newVersion} created");
        }

        return self::SUCCESS;
    }
}
