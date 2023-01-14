# Services

All services should reside in the `/app/Services` directory, and extend `Bayfront\Bones\Service`.
In the event a service does not exist in this directory, Bones will attempt to use its own service with the same name, if existing.

Models may throw a `Bayfront\Bones\Exceptions\ServiceException` exception in the constructor.

If a service needs its own constructor, be sure to invoke `parent::__construct()` within it.

Services should be instantiated via the [get_service](https://github.com/bayfrontmedia/bones/blob/master/_docs/helpers.md#get_service) helper.
This allows them to be managed by the container so that any classes existing in the container 
can be injected into the constructor.

The `app.service` event is executed whenever a service is constructed and passes the class instance as a parameter.

## Creating a new service

The easiest way of creating a new service is from the command line:

```
php bones make:service NAME
```

## Bones services

There are currently no services provided by Bones.