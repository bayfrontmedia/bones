<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseSchemaInterface;

/**
 * See: https://swagger.io/specification/#schema-object
 */
class OpenApiResponseSchema implements ApiResponseSchemaInterface
{

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getProperties(): array
    {
        return (array)Arr::get($this->definition, 'properties', []);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredProperties(): array
    {
        return (array)Arr::get($this->definition, 'required', []);
    }

    /**
     * @inheritDoc
     */
    public function isRequired(string $property): bool
    {
        return in_array($property, $this->getRequiredProperties());
    }

}