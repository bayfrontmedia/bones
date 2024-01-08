# Services: Events

Under the hood, [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) is used to manage events and filters.

Bones customizes the use of this library with its built-in service at `Bayfront\Bones\Application\Services\Events\EventService`,
which is added to the service container with alias `events`.

The concept of events is that certain [events](#events) are triggered by the app at different times,
and one or multiple [subscriptions](#creating-a-subscription) can be assigned to execute when a particular event occurs.

Since the service container is used to instantiate the event subscribers, you can type-hint any dependencies
in its constructor, and the container will use dependency injection to resolve them for you.

## Methods

- [getSubscriptions](#getsubscriptions)
- [addSubscriber](#addsubscriber)
- [doEvent](#doevent)

<hr />

### getSubscriptions

**Description:**

Return an array of all event subscriptions.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```php
$subscriptions = $events->getSubscriptions();
```

<hr />

### addSubscriber

**Description:**

Add event subscriber.

**Parameters:**

- `$subscriber` (`Bayfront\Bones\Interfaces\EventSubscriberInterface`)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\ServiceException`

<hr />

### doEvent

**Description:**

Execute all subscriptions for an event in order of priority.

**Parameters:**

- `$event` (string)
- `...$arg` (Optional arguments)

**Returns:**

- (void)

**Example:**

```php
$events->doEvent('app.event');
```

## Creating a subscription

To create an event subscriber, use the `make:event` [console command](#console-commands).

### Caching event subscribers

Performance can be improved by caching event subscribers.
This should only be done in a production environment, where subscriptions will remain unchanged.

Events can be cached with the `php bones cache:save --events` console command.

For more information, see [console commands](../usage/console.md). 

## Events

Bones is set up to use the following events, in a typical order of execution:

- `bones.down`: Executes when bones is put into maintenance mode using the `php bones down` [console command](../usage/console.md). The contents of the `down.json` file is passed as a parameter.
- `bones.up`: Executes when bones is taken out of maintenance mode using the `php bones up` [console command](../usage/console.md).
- `bones.start`: Executes just after Bones has initialized, and before the app is bootstrapped. 
This event is not accessible by the app.
- `app.bootstrap`: Executes just after the app's `/resources/bootstrap.php` file has been loaded. 
The [service container](../usage/container.md) is passed as a parameter.
- `app.cli`: Executes when the app interface is `CLI`. The [Symfony Console application](../usage/console.md) is passed as a parameter.
- `app.schedule.start`: Executes before running scheduled jobs from the command line using `php bones schedule:run`.
  The scheduler's [class instance](scheduler.md) is passed as a parameter.
- `app.schedule.end`: Executes after all scheduled jobs are completed from the command line
using `php bones schedule:run`. The response of the scheduler's [run method](https://github.com/bayfrontmedia/cron-scheduler#run) is passed as a parameter.
- `app.http`: Executes when the app interface is `HTTP` just before [the router](router.md) (if existing) resolves the request.
- `app.dispatch`: Executes after the router (if existing) resolves the request, and just before the request is dispatched. An array containing the keys `type`, `destination`, `params` and `status` is passed as a parameter ([more info](https://github.com/bayfrontmedia/route-it#resolve)).
- `app.controller`: Executes when a [controller](../usage/controllers.md) is constructed. The controller's class instance is passed as a parameter.
- `app.model`: Executes when a [model](../usage/models.md) is constructed. The model's class instance is passed as a parameter.
- `app.service`: Executes when a [service](../usage/services.md) is constructed. The service's class instance is passed as a parameter.
- `bones.exception`: Executes when a `Bayfront\Bones\Exceptions\BonesException` is thrown. 
This event accepts two parameters: the [Response](response.md) service and the [thrown exception](../usage/exceptions.md).
- `bones.end`: Executes as the last event.

The underlying PHP Hooks library also has its own default events:

- `always`: Always executed whenever `doEvent()` is called, regardless of the name.
- `destruct`: Executes when the PHP Hooks library destructs.

## Console commands

The following [console commands](../usage/console.md) can be used with relation to events:

- `php bones event:list`
- `php bones make:event`