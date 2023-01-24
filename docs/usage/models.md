# Usage: Models

All models should reside in the `/app/Models` directory, and extend `Bayfront\Bones\Abstracts\Model`.

If a controller needs its own constructor, be sure to invoke `parent::__construct(EventService $events)` within it.

Note that `EventService` is required by the abstract controller.
When a model is created, the `app.model` event is executed.
The model instance is passed as a parameter to this event.

Since the service container is used to instantiate the model, you can type-hint any dependencies
in its constructor, and the container will use dependency injection to resolve them for you.

## Console commands

The following [console commands](console.md) can be used with relation to models:

- `make:model`