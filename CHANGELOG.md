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

## [1.2.0]

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