# Filters

Under the hood, [PHP Hooks](https://github.com/bayfrontmedia/php-hooks) library is used to manage actions and filters,
and is added to the services container as `hooks`.

The concept is that certain values are filtered, adding the ability to alter the value before it is used by the app.
One or multiple [filters](#creating-a-new-filter) can be assigned to filter a value.

## Creating a new filter

The easiest way of creating a new filter is from the command line:

```shell
php bones make:filter NAME
```

All filters must extend `Bayfront\Bones\Filter` and implement `Bayfront\Bones\Interfaces\FilterInterface`.

**Services available**

- Container as `$this->container`
- HTTP response as `$this->response`

How filters are loaded depends on the [app config settings](app.md#filters).

To get a list of all hooked filters (valid for the CLI interface), the `php bones filter:list` command can be used.
For more information, see [CLI](libraries/cli.md).

## Values

By default, Bones is set up to filter the following values:

- `logs.context`: Used by the [Logs helpers](helpers.md#services-helpers) to filter the log context array.
- `router.parameters`: Used to inject global parameters into every router destination.
- `translate`: Used by some [Translate helpers](helpers.md#services-helpers) to filter translated strings.
- `veil.view`: Used by some [Veil helpers](helpers.md#services-helpers) to filter the returned HTML from a view.

### Filtering a value

Values can be filtered by the [do_filter](helpers.md#do_filter) helper.