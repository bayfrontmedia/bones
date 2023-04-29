# Usage: Services

All services should reside in the `/app/Services` directory, and extend `Bayfront\Bones\Abstracts\Service`.

If a service needs its own constructor, be sure to invoke `parent::__construct(EventService $events)` within it.

Note that `EventService` is required by the abstract service.
When a service is created, the `app.service` event is executed.
The service instance is passed as a parameter to this event.

Since the service container is used to instantiate the service, you can type-hint any dependencies
in its constructor, and the container will use dependency injection to resolve them for you.

## Console commands

The following [console commands](console.md) can be used with relation to services:

- `php bones make:service`