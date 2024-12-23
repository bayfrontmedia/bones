# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities

## [5.3.1]- 2024.12.23

### Added

- Added support for route destinations to be defined as an array of class & method.

### Fixed

- Fixed bug where `router.parameters` filter was not defined.
- Fixed bug in `ExceptionHandler` not returning previous trace.

## [5.3.0]- 2024.10.30

### Added

- Added `Helpers` utility class.

## [5.2.0]- 2024.09.16

### Added

- Added support for using a specific database connection name with `migrate:up`, `migrate:down` and `migration:list` console commands.

### Changed

- Updated `simple-pdo` dependency to v5.0.0.

## [5.1.1]- 2024.09.10

### Fixed

- Fixed Simple PDO namespace bug.

## [5.1.0]- 2024.09.10

### Changed

- Updated `simple-pdo` dependency to v4.0.0.

## [5.0.1]- 2024.09.09

### Changed

- Updated all dependencies.

## [5.0.0]- 2024.09.04

### Added

- Added the `createHash`, `createPasswordHash` and `isPasswordHashValid` methods to the `App` utility class.

### Changed

- Changed all database migrations to use the `bones.migrations` filter.
- Updated GitHub issue templates.
- Updated all dependencies.

## [4.2.0]- 2024.04.03

### Changed

- Updated `Bayfront\Bones\Interfaces\ExceptionHandlerInterface` to exclude the `report` method.

## [4.1.1]- 2024.01.28

### Added

- Added the `Response` service to be passed as a parameter to the `bones.end` event.

## [4.1.0]- 2024.01.11

### Added

- Added `InvalidArgumentException`.

### Changed

- Updated names of router and Veil resources when installing service via the command line.
- Updated `App::abort()` method.

## [4.0.1]- 2024.01.08

### Added

- Added maintenance mode status in `php bones about:bones` console command.

### Fixed

- Fixed bug in `CacheSave` class when using `unlink`.

## [4.0.0]- 2024.01.08

### Added

- Added `php bones down` and `php bones up` console commands for managing maintenance mode.
- Added the `app.dispatch` event in the `RouterDispatcher` class.
- Added recursive lookup of all events, filters and commands. 
- Added caching for console commands, config files, events and filters.

## Changed

- Updated the `ExceptionHandler` to look for a `Bayfront\MultiLogger\Log` instead of a `Monolog\Logger` instance.
- Updated `EventService` and `FilterService` locations, and added the `EventSubscription` and `FilterSubscription` classes to be used within subscribers.
- Updated all dependencies.

### Removed

- Removed commands, events and filters from being loaded via the app configuration array.

### Fixed

- Fixed bug where the `bones.end` event was not being executed after `bones.exception`.

## [3.2.0]- 2023.05.11

### Added

- Added `$code` parameter to the `App::abort()` method.
- Added `set` and `has` methods in the `App` utility class.

### Changed

- Updated dependencies.

### Fixed

- Fixed bug where entire exception stack trace was being reported when debug mode was `false`.

## [3.1.4]- 2023.05.05

### Fixed

- Fixed bug in initial database service configuration.
- Fixed documentation bug for console command `php bones migrate:up`.
- Fixed other documentation errors.

## [3.1.3]- 2023.04.05

### Added

- Added `about.bones` filter.

### Changed

- Updated dependencies.

## [3.1.2]- 2023.04.04

### Changed

- Simplified `php bones migration:list` command.

## [3.1.1]- 2023.04.01

### Changed

- Updated documentation.

### Fixed

- Fixed bug in `php bones migration:list` command if `migrations` table does not yet exist.

## [3.1.0]- 2023.02.09

### Added

- Added support for database migrations.

## [3.0.1]- 2023.02.01

### Changed

- Updated the `App::abort()` method and the exception handler to use the existing `Response` class, 
if existing in the container.

## [3.0.0]- 2023.01.27

### Added

- Added support for PHP 8.

### Removed

- Removed the optional `filesystem` and `logs` services.

## [2.0.5]- 2023.01.26

### Fixed

- Fixed `DirectoryIterator` bug.

## [2.0.4]- 2023.01.26

### Added

- Added the `php bones install:key` command.

### Changed

- Changed the `php bones key:create` command to `php bones make:key`.

## [2.0.3]- 2023.01.25

### Removed

- Removed the `php bones install:bare` command.

## [2.0.2]- 2023.01.25

### Added

- Added deploy backup path to the `php bones about:app` command.

### Changed

- Updated the `Bootstrap` event subscriber when running the `php bones install:bare` command.
- Updated the default template styling installed with the `php bones install:service --veil` command.
- Moved `DeployApp` and `DeployPurge` console commands to install with `php bones install:bare`.
- Renamed the `php bones about:app` command to `php bones about:bones`.
- Updated documentation.

### Removed

- Removed all occurrences of `shell_exec` in console commands since this may not be available to use in all environments.

## [2.0.1]- 2023.01.24

### Changed

- Added `composer update` to `php bones install:bare` console command.

### Fixed

- Fixed bug in `php bones install:bare` when copying `APP_KEY` to `.env`.
- Fixed bug in install console commands where `.env` file was omitted from Git.

## [2.0.0]- 2023.01.24

### Added

- Complete refactoring of project. 

## [1.4.1]- 2022.01.19

### Added

- Added `get_locale` helper function.
- Added GitHub workflow

### Changed

- Updated `composer.json`.

## [1.4.0]- 2021.10.21

### Changed

- Multiple documentation improvements.

### Removed

- Removed the `BonesApi` and `BonesAuth` services, as they were not production ready and had not yet been used.

## [1.3.0]- 2021.09.14

### Added

- Added filesystem into controllers by default.

### Changed

- Updated vendor dependencies.

### Fixed

- Fixed bug where string was not being trimmed in the `App\useHelper()` method.

## [1.2.4]- 2021.03.13

### Changed

- Updated vendor dependencies.

## [1.2.3]- 2021.02.26

### Added

- Added support for `include` query parameter in the `parseQuery` method of the `BonesApi` service.
- Added support for querying relationships in the `BonesAuth` service.

## [1.2.2]- 2021.02.24

### Fixed

- Fixed pagination keys name in the `BonesAuth` service.

## [1.2.1]- 2021.02.16

### Fixed

- Fixed bug in the `parseQuery` method of the `BonesApi` service returning empty array when no sort order was specified.

## [1.2.0]- 2021.02.15

### Added

- Added the following helper methods:
    - `in_container`
    - `put_in_container`
    - `set_in_container`

- Added the `BonesAuth` service.

### Changed

- Updated the way page size is handled for the `BonesApi` service `parseQuery` method.

## [1.1.4]- 2021.02.03

### Changed

- Updated the exceptions thrown by the `BonesApi` service `parseQuery` method.

## [1.1.3]- 2021.01.29

### Changed

- Updated vendor dependencies.

## [1.1.2] - 2021.01.23

### Fixed

- Fixed bug in `do_event` helper
- Remove duplicate fields in the `parseQuery` method of the `BonesApi` service.

## [1.1.1] - 2021.01.19

### Added

- Added `$check_accept` parameter to the `BonesApi` service `start` method.

## [1.1.0] - 2021.01.15

### Changed

- Updated vendor dependencies.
- Added `parseQuery` method to the BonesApi service.
- Updated documentation.

## [1.0.1] - 2020.12.25

### Fixed

- Updated `Bayfront\Bones\App::start()` method to check if file exists before attempting to load environment variables from the `.env` file.

## [1.0.0] - 2020.12.05

### Added

- Initial release.