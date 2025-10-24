<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

/**
 * ModuleServiceProvider avec constructeur personnalisé
 * 
 * Ce provider reçoit $app ET $module dans son constructeur
 * Il est instancié manuellement par Anvil
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    protected BaseModule $module;

    /**
     * Constructeur personnalisé recevant le module
     * 
     * @param Application $app
     * @param BaseModule $module
     */
    public function __construct(Application $app, BaseModule $module)
    {
        parent::__construct($app);
        $this->module = $module;
    }

    /**
     * Register est appelé immédiatement par Anvil
     */
    public function register(): void
    {
        // Les enfants peuvent override
    }

    /**
     * Boot est appelé soit immédiatement si l'app est bootée,
     * soit par Laravel au moment du boot de l'app
     */
    public function boot(): void
    {
        // Les enfants peuvent override
        
        // Note : Les routes et vues sont déjà chargées par Anvil
        // Ce provider peut faire des choses supplémentaires :
        // - Publier des assets
        // - Enregistrer des view composers
        // - Enregistrer des event listeners
        // - etc.
    }

    /**
     * Helper pour publier des assets
     */
    protected function publishAssets(?string $tag = null): void
    {
        $tag = $tag ?? strtolower($this->module->getName()) . '-assets';
        
        $assetsPath = $this->module->getPath('Resources/assets');
        if (is_dir($assetsPath)) {
            $this->publishes([
                $assetsPath => public_path('vendor/' . strtolower($this->module->getName())),
            ], $tag);
        }
    }

    /**
     * Helper pour publier la config
     */
    protected function publishConfiguration(?string $tag = null): void
    {
        if (!$this->module instanceof \IronFlow\Contracts\ConfigurableInterface) {
            return;
        }

        $tag = $tag ?? strtolower($this->module->getName()) . '-config';
        $configPath = $this->module->getConfigPath();
        $configKey = $this->module->getConfigKey();

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path("{$configKey}.php"),
            ], $tag);
        }
    }
}