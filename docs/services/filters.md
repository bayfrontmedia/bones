# Services: Filters

Under the hood, [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) is used to manage events and filters.

Bones customizes the use of this library with its built-in service at `Bayfront\Bones\Application\Services\Filters\FilterService`,
which is added to the service container with alias `filters`.

The concept of filters is that certain values are [filtered](#filters), adding the ability to alter the value
before it is sent in the response. 
One or multiple [subscriptions](#creating-a-subscription) can be assigned to each filter.

Since the service container is used to instantiate the filter subscribers, you can type-hint any dependencies
in its constructor, and the container will use dependency injection to resolve them for you.

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

To create a filter subscriber, use the `php bones make:filter` [console command](#console-commands).

### Caching filter subscribers

Performance can be improved by caching filter subscribers.
This should only be done in a production environment, where subscriptions will remain unchanged.

Filters can be cached with the `php bones cache:save --filters` console command.

For more information, see [console commands](../usage/console.md).

## Filters

In addition to those which may be created when installing an [optional service](../README.md), 
Bones utilizes the following filters:

- `about.bones` - Merge an array of data to that which is returned with the `php bones about:bones` [console command](../usage/console.md). 
Default array keys used by Bones cannot be overwritten.

## Console commands

The following [console commands](../usage/console.md) can be used with relation to filters:

- `php bones filter:list`
- `php bones make:filter`