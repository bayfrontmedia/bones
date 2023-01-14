# Actions

Under the hood, [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) library is used to manage actions and filters, 
and is added to the services container as `hooks`.

The concept is that certain [events](#events) are triggered by the app at different times, 
and one or multiple [actions](#creating-a-new-action) can be assigned to execute when a particular event occurs.

## Creating a new action

The easiest way of creating a new action is from the command line:

```shell
php bones make:action NAME
```

All actions must extend `Bayfront\Bones\Action` and implement `Bayfront\Bones\Interfaces\ActionInterface`.

**Services available**

- Container as `$this->container`
- HTTP response as `$this->response`

How actions are loaded depends on the [app config settings](app.md#actions).

To get a list of all hooked actions (valid for the CLI interface), the `php bones action:list` command can be used.
For more information, see [CLI](libraries/cli.md).

## Caching

Due to the resource-intensive nature of loading all the actions, when the `actions.cache` config array is `true`,
all the instantiated actions will be cached in `storage/app/cache`.

For this reason, it is important that any environment-specific logic is kept in the `action` method, as `isActive`
will not be rechecked when reading from the cache.

When any changes to actions have been made, the action cache will need to be cleared.
You can do so by using the `php bones cache:clear actions` command.

## Events

Bones is set up to use the following events, in order of execution:

- `bones.init`: Executes just after Bones has initialized.
- `app.bootstrap`: Executes just after the app's `/resources/bootstrap.php` file has been loaded.
- `app.cli`: Executes when the app interface is `CLI`. The Symfony Console application is passed as a parameter.
- `app.schedule.start`: Executes before running scheduled jobs (cron)
- `app.schedule.end`: Executes after all scheduled jobs are completed. The `$result` is passed as a parameter.
- `app.http`: Executes when the app interface is `HTTP` just before the router dispatches the request.
- `app.controller`: Executes when any controller is constructed.
- `app.controller.web`: Executes when `Bayfront\Controllers\WebController` is constructed.
- `app.model`: Executes when a model is constructed.
- `bones.exception`: Executes when a `Bayfront\Bones\Exceptions\BonesException` is thrown. This event accepts two parameters: the thrown exception object and the [HTTP Response](container.md) library.
- `bones.shutdown`: Executes as the last event.

The php-hooks library also has its own default events:

- `always`: Always executed whenever `doEvent()` is called, regardless of the name.
- `destruct`: Executes when the PHP Hooks library destructs.

### Triggering an event

Events can be triggered by the [do_event](helpers.md#do_event) helper.