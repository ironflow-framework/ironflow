# Changelog

All notable changes to IronFlow will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2025-10-12

### Added

- Add Commands for generate files in the module

### Changed

- Fix BaseModule Contructor
- Fix Migrations Auto-Discovery
- Fix Stubs avec bon Namespace + Commandes Make:*

## [2.0.0] - 2025-10-11

### Added

- ExportableInterface allowing modules to declare resources available for export.
- Add Lazy Loading system
- Add Cache multi-niveaux
- Add Hot Reload (dev)
- Support classes
- Add stub files

### Changed

- BaseModule refactored to remove the ModuleInterface implementation.
- Renamed method metadata() → createMetadata() in BaseModule.
- Update ModuleMetaData & ModuleState
- Updated all global interface contracts for consistency and clarity.
- Using Laravel Cache system
- Exceptions classes
- Update `IronFlowServiceProvider`
- Updated README and documentation to reflect new patterns and best practices.

### Removed

- ModuleInterface (now deprecated).

## [1.3.0] - 2025-10-06

Added

Introduced expose() method for modules to define public APIs and shared resources.

Added collectExposed() to Anvil for centralized resource collection.

Enabled explicit inter-module communication through the exposure system.

## [1.2.0] - 2025-10-05

### Added

- call() method in BaseModule for executing Artisan commands within modules.
- app() method in BaseModule for easy access to the Laravel application instance.

## [1.1.0] - 2025-10-05

### Changed

- Updated module folder structure:
- Moved Controllers/ → Http/.
- Improved MakeModuleControllerCommand.
- Updated ModuleCreateCommand for new directory layout.

## [1.0.0] - 2025-10-05

### Added

- Initial release of the IronFlow modular framework.
- Core Anvil module manager with dependency resolution.
- Full module lifecycle management (REGISTERED → PRELOADED → BOOTING → BOOTED).
- Comprehensive CLI suite:
  - ironflow:module:create – Create new modules.
  - ironflow:module:publish – Prepare modules for Packagist.
  - ironflow:module:install – Install modules from Composer or local sources.
  - ironflow:make:* – Generators for controllers, models, and services.
  - ironflow:discover – Auto-discover all modules.
  - ironflow:list – List registered modules.
  - ironflow:info – Display module metadata.
  - ironflow:boot-order – View module boot sequence.
  - ironflow:cache:* – Cache management commands.
- BaseModule abstract class with shared core functionality.
- Module contracts (RoutableInterface, ViewableInterface, etc.).
- Circular dependency detection and priority-based boot ordering.
- Module metadata system and service override support.
- Automatic registration of routes, views, and migrations.
- Complete test suite using Pest.
- Integrated GitHub Actions CI/CD workflows.
- Full developer documentation.
- Features
  - Laravel 12+ compatibility.
  - PHP 8.3 support.
  - Module caching for faster performance.
  - Auto-discovery and auto-boot mechanisms.
  - Strict mode validation.
  - Seamless module publishing workflow.
- Example Blog module.
- Orchestra Testbench integration.
- Developer Experience
- Clean, intuitive API design.
- Extensive documentation with examples.
- Stub-based generators for rapid development.
- Robust error handling and logging.
- Developer-friendly messages and feedback.

## Releases

[Unreleased]: https://github.com/ironflow-framework/ironflow/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/ironflow/ironflow-framework/releases/tag/v2.1.0
[2.0.0]: https://github.com/ironflow/ironflow-framework/releases/tag/v2.0.0
[1.3.0]: https://github.com/ironflow-framework/ironflow/releases/tag/v1.3.0
[1.2.0]: https://github.com/ironflow-framework/ironflow/releases/tag/v1.2.0
[1.1.0]: https://github.com/ironflow-framework/ironflow/releases/tag/v1.1.0
[1.0.0]: https://github.com/ironflow-framework/ironflow/releases/tag/v1.0.0
