# Command line

The [Climate](https://github.com/thephpleague/climate) library is used to manage command line functionality.

This library will be added to the services container as `cli` if the `/resources/cli.php` file is accessed.

**Example usage:**

```
php bones
```

This file is loaded immediately after the `app.bootstrap` event.
The `app.cli` event is fired immediately after the Climate library is added to the container.

Command line functionality includes:

- Create new controller
- Create new exception
- Create new model
- Create new service
- Create a secure key