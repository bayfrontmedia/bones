# Constants

Constants are globally accessible identifiers which can be used throughout your application.

Instead of using any of the `APP_*_PATH` constants directly, it is recommended to use their associated [helper function](helpers.md) instead.

The following constants are used by Bones:

- `APP_ROOT_PATH`- Root path to the application's root directory
- `APP_PUBLIC_PATH`- Path to the application's public directory
- `APP_CONFIG_PATH`- Path to the application's `/config` directory
- `APP_RESOURCES_PATH`- Path to the application's `/resources` directory
- `APP_STORAGE_PATH`- Path to the application's `/storage` directory
- `BONES_ROOT_PATH`- Root path to the Bones directory
- `BONES_RESOURCES_PATH`- Path to the Bones `/resources` directory
- `BONES_VERSION`- Currently installed Bones version
- `IS_CRON` If app is running from a cron job
- `IS_CLI` If app is running from the CLI