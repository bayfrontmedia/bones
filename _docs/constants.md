# Constants

Constants are globally accessible identifiers which can be used throughout your application.

Instead of using any of the `APP_*_PATH` constants directly, it is recommended to use their associated [helper function](helpers.md) instead.

The following constants are used by Bones:

- `APP_BASE_PATH`- Base path to the application's directory
- `APP_PUBLIC_PATH`- Path to the application's public directory
- `APP_CONFIG_PATH`- Path to the application's `/config` directory
- `APP_RESOURCES_PATH`- Path to the application's `/resources` directory
- `APP_STORAGE_PATH`- Path to the application's `/storage` directory
- `BONES_BASE_PATH`- Base path to the Bones directory
- `BONES_RESOURCES_PATH`- Path to the Bones `/resources` directory
- `BONES_START`- `microtime` timestamp of when the app was started
- `BONES_END`- `microtime` timestamp of when the app execution completed (defined immediately before the `bones.shutdown` event)
- `BONES_VERSION`- Currently installed Bones version