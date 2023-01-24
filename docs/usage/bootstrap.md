# Usage: Bootstrap

The `/resources/bootstrap.php` file is used to bootstrap your application by interacting with the
service container in order to add any required dependent services.

This file is required by Bones.

The [service container](container.md) is available in this file as `$container`, which already contains
all the default services.

Example:

```php
use Bayfront\Bones\Application\Utilities\App;

/** @var Bayfront\Container\Container $container */

$container->set('Fully\Namespaced\ClassName', function () {
    return new ClassName(App::getConfig('example.config')); // Implements the interface
});

$container->setAlias('Fully\Namespaced\Interface', 'Fully\Namespaced\ClassName');
```

Once services have been added to the container, they can be type-hinted in the constructor
of classes created with the container throughout your app. 
The container will use dependency injection to take care of the rest.

Services which do not require any dependencies or additional configuration in the 
constructor do not need to be added to the container.

Classes created with the container include:

- [Controllers](controllers.md)
- [Console commands](console.md)
- [Event subscribers](../services/events.md)
- [Filter subscribers](../services/filters.md)

For more information, see [container](container.md).