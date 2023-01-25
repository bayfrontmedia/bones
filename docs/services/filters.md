# Services: Filters

Under the hood, [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) is used to manage events and filters.

Bones customizes the use of this library with its built-in service at `Bayfront\Bones\Application\Services\FilterService`,
which is added to the service container with alias `filters`.

The concept of filters is that certain values are [filtered](#filters), adding the ability to alter the value
before it is sent in the response. 
One or multiple [subscriptions](#creating-a-subscription) can be assigned to each filter.

Since the service container is used to instantiate the filter subscribers, you can type-hint any dependencies
in its constructor, and the container will use dependency injection to resolve them for you.

How filters are loaded depends on the [app config settings](../usage/config.md#filters).

## Methods

- [getSubscriptions](#getsubscriptions)
- [addSubscriber](#addsubscriber)
- [doFilter](#dofilter)

<hr />

### getSubscriptions

**Description:**

Return an array of all filter subscriptions.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```php
$subscriptions = $filters->getSubscriptions();
```

<hr />

### addSubscriber

**Description:**

Add filter subscriber.

**Parameters:**

- `$subscriber` (`Bayfront\Bones\Interfaces\FilterSubscriberInterface`)

**Returns:**

- (void)

**Throws:**

- `Bayfront\Bones\Exceptions\ServiceException`

<hr />

### doFilter

**Description:**

Execute all subscriptions for a filter in order of priority.

**Parameters:**

- `$name` (string)
- `$value` (mixed)

**Returns:**

- (mixed)

**Example:**

```php
$filtered = $filters->doFilter('example.filter', $filtered);
```

## Creating a subscription

To create a filter subscriber, use the `make:filter` [console command](#console-commands).

## Filters

Bones does not utilize any filters, except for those which may be created when installing an [optional service](../README.md).

## Console commands

The following [console commands](../usage/console.md) can be used with relation to filters:

- `filter:list`
- `make:filter`