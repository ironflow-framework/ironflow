<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Registry\ModuleRegistry;

class RegistrySearchCommand extends Command
{
    protected $signature = 'ironflow:registry:search {query : Search query}
                            {--type= : Module type filter}
                            {--tag= : Tag filter}
                            {--author= : Author filter}';

    protected $description = 'Search for modules in the IronFlow registry';

    public function handle(ModuleRegistry $registry): int
    {
        $query = $this->argument('query');

        $filters = array_filter([
            'type' => $this->option('type'),
            'tag' => $this->option('tag'),
            'author' => $this->option('author'),
        ]);

        $this->info("Searching for: {$query}");
        $this->newLine();

        $results = $registry->search($query, $filters);

        if (empty($results)) {
            $this->warn('No modules found.');
            return self::SUCCESS;
        }

        $this->info("Found " . count($results) . " module(s):");
        $this->newLine();

        $rows = [];
        foreach ($results as $module) {
            $rows[] = [
                $module['package_name'],
                $module['version'],
                $module['downloads'] ?? 0,
                substr($module['description'] ?? '', 0, 50),
            ];
        }

        $this->table(
            ['Package', 'Version', 'Downloads', 'Description'],
            $rows
        );

        $this->newLine();
        $this->info('To install: composer require {package-name}');

        return self::SUCCESS;
    }
}
