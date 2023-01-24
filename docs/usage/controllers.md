# Usage: Controllers

All controllers should reside in the `/app/Controllers` directory, and extend `Bayfront\Bones\Abstracts\Controller`.

If a controller needs its own constructor, be sure to invoke `parent::__construct(EventService $events)` within it.

Note that `EventService` is required by the abstract controller.
When a controller is created, the `app.controller` event is executed.
The controller instance is passed as a parameter to this event.

Since the service container is used to instantiate the controller, you can type-hint any dependencies 
in its constructor, and the container will use dependency injection to resolve them for you.

## Console commands

The following [console commands](console.md) can be used with relation to controllers:

- `make:controller`