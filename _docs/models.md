# Models

All models should reside in the `/app/Models` directory, and extend `Bayfront\Bones\Model`.
In the event a model does not exist in this directory, Bones will attempt to use its own model with the same name, if existing.

Models may throw a `Bayfront\Bones\Exceptions\ModelException` exception in the constructor.

If a model needs its own constructor, be sure to invoke `parent::__construct()` within it.

Models should be instantiated via the `get_model()` helper.
This allows them to be managed by the container so that any classes existing in the container
can be injected into the constructor.

The `app.model` event is executed whenever a model is constructed and passes the class instance as a parameter.

## Creating a new model

The easiest way of creating a new model is from the command line:

```
php bones make:model NAME
```