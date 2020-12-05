# Services

You can write your own services by placing them in the `/app/Services` directory.
In the event a service does not exist in this directory, Bones will attempt to use its own service with the same name, if existing.

Services should be instantiated via the [get_service](https://github.com/bayfrontmedia/bones/blob/master/_docs/helpers.md#get_service) helper.
This allows them to be managed by the container, which also handles dependency injection.

## Creating a new service

The easiest way of creating a new service is from the command line:

```
php /path/to/resources/cli.php
```

## Bones services

The following services are included with Bones:

- [BonesApi](services/bonesapi.md)