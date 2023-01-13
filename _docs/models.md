# Models

All models should reside in the `/app/Models` directory, and extend `Bayfront\Bones\Model`.
In the event a model does not exist in this directory, Bones will attempt to use its own model with the same name, if existing.

Models may throw a `Bayfront\Bones\Exceptions\ModelException` exception in the constructor.

If a model needs its own constructor, be sure to invoke `parent::__construct()` within it.

Services available within a model:

- Container as `$this->container`
- Filesystem as `$this->filesystem` (if existing in container)
- Database as `$this->db` (if existing in container)

Models should be instantiated via the `get_model()` helper.
This allows them to be managed by the container, which also handles dependency injection.

The `app.model` event is executed whenever a model is constructed.

## Creating a new model

The easiest way of creating a new model is from the command line:

```
php bones make:model NAME
```