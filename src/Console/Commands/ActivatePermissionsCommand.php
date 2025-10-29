<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ActivatePermissionsCommand extends Command
{
    protected $signature = 'ironflow:permissions:activate';
    protected $description = 'Activate module permissions and create ironflow permission migrations';

    public function handle(): int
    {
        $migrationPath = __DIR__ . '/../../../database/migrations/2025_01_01_000001_create_ironflow_permissions_tables.php';

        $this->output->info("Activate IronFlow Permission System");

        File::copy($migrationPath, database_path('migrations'));

        $destinationFile = database_path('migrations/2025_01_01_000001_create_ironflow_permissions_tables.php');

        $this->info("Migrations created");

        if ($this->confirm("Execute new migrations ?")) {
            $this->info("Running migrations...");
            Artisan::call('migrate', [
                '--path' => $destinationFile
            ]);
            $this->info(Artisan::output());
        } else {
            $this->warn("Run : php artisan migrate");
        }

        $this->output->success("Ironflow Module Permission System Activate");

        return self::SUCCESS;
    }
}
