# Bootstrap

The `/resources/bootstrap.php` file is responsible for bootstrapping the initial app environment.
This file is required by Bones.

This file is loaded immediately after the `bones.init` event.
The `app.bootstrap` event is fired immediately after this file is loaded.