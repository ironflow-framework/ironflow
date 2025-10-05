# Changelog

All notable changes to IronFlow will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-10-05

### Changed

- Update module structure :
  - Move Controllers/ to Http/
- Update MakeModuleControllerCommand
- Update ModuleCreateCommand

## [1.0.0] - 2025-10-05

### Added

- Initial release of IronFlow Framework
- Core Anvil module manager with dependency resolution
- Module lifecycle management (REGISTERED → PRELOADED → BOOTING → BOOTED)
- Comprehensive CLI commands:
  - `ironflow:module:create` - Create new modules
  - `ironflow:module:publish` - Prepare modules for Packagist
  - `ironflow:module:install` - Install modules from Composer or local path
  - `ironflow:make:*` - Generators for controllers, models, services, etc.
  - `ironflow:discover` - Auto-discover modules
  - `ironflow:list` - List all registered modules
  - `ironflow:info` - Display module information
  - `ironflow:boot-order` - Show module boot order
  - `ironflow:cache:*` - Cache management commands
- BaseModule abstract class with common functionality
- Module contracts (RoutableInterface, ViewableInterface, etc.)
- Circular dependency detection
- Priority-based boot ordering
- Module metadata system
- Service override capabilities
- Automatic route, view, and migration registration
- Complete test suite with Pest
- GitHub Actions CI/CD workflows
- Comprehensive documentation

### Features

- Laravel 12 compatibility
- PHP 8.3 support
- Module caching for performance
- Auto-discovery and auto-boot
- Strict mode for validation
- Module publishing workflow
- Example Blog module
- Orchestra Testbench integration

### Developer Experience

- Clean, intuitive API
- Extensive documentation
- Code examples
- Stub-based generators
- Error handling and logging
- Developer-friendly error messages

[Unreleased]: https://github.com/ironflow-framework/ironflow/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ironflow/ironflow-framework/releases/tag/v1.0.0
