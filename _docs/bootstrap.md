# Bootstrap

The `/resources/bootstrap.php` file is responsible for bootstrapping the initial app environment.
This file is required by Bones.
The container is available in this file as `$container`.

This file is loaded immediately after the `bones.init` event.
The `app.bootstrap` event is fired immediately after this file is loaded.