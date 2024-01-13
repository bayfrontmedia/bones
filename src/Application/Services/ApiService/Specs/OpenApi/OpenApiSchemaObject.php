<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSchemaObjectInterface;

/**
 * See: https://swagger.io/specification/#schema-object
 */
class OpenApiSchemaObject implements ApiSchemaObjectInterface
{

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return (array)Arr::get($this->definition, 'properties', []);
    }

    public function getRequiredProperties(): array
    {
        return (array)Arr::get($this->definition, 'required', []);
    }

    public function isRequired(string $property): bool
    {
        return in_array($property, $this->getRequiredProperties());
    }

}