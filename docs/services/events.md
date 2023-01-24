# Services: Events

Under the hood, [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) is used to manage events and filters.

Bones customizes the use of this library with its built-in service at `Bayfront\Bones\Application\Services\EventService`,
which is added to the service container with alias `events`.

The concept of events is that certain [events](#events) are triggered by the app at different times,
and one or multiple [subscriptions](#creating-a-subscription) can be assigned to execute when a particular event occurs.

Since the service container is used to instantiate the event subscribers, you can type-hint any dependencies
in its constructor, and the container will use dependency injection to resolve them for you.

How events are loaded depends on the [app config settings](../usage/config.md#events).

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

## Events

Bones is set up to use the following events, in a typical order of execution:

- `bones.start`: Executes just after Bones has initialized, and before the app is bootstrapped. 
This event is not accessible by the app.
- `app.bootstrap`: Executes just after the app's `/resources/bootstrap.php` file has been loaded. 
The service container is passed as a parameter.
- `app.cli`: Executes when the app interface is `CLI`. The Symfony Console application is passed as a parameter.
- `app.schedule.start`: Executes before running scheduled jobs from the command line using `schedule:run`.
  The scheduler's class instance is passed as a parameter.
- `app.schedule.end`: Executes after all scheduled jobs are completed from the command line
using `schedule:run`. The `$result` is passed as a parameter.
- `app.http`: Executes when the app interface is `HTTP` just before the router dispatches the request.
- `app.controller`: Executes when a controller is constructed. The controller's class instance is passed as a parameter.
- `app.model`: Executes when a model is constructed. The model's class instance is passed as a parameter.
- `app.service`: Executes when a service is constructed. The service's class instance is passed as a parameter.
- `bones.exception`: Executes when a `Bayfront\Bones\Exceptions\BonesException` is thrown. 
This event accepts two parameters: the thrown exception object and the [Response](response.md) service.
- `bones.end`: Executes as the last event.

The underlying PHP Hooks library also has its own default events:

- `always`: Always executed whenever `doEvent()` is called, regardless of the name.
- `destruct`: Executes when the PHP Hooks library destructs.

## Console commands

The following [console commands](../usage/console.md) can be used with relation to events:

- `event:list`
- `make:event`