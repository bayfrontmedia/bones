<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiSchemaObjectInterface;

/**
 * See: See: https://swagger.io/specification/#response-object
 */
class OpenApiResponse implements ApiResponseInterface
{

    protected array $definition;

    /**
     * @param array $definition
     */
    public function __construct(array $definition)
    {
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
    public function getDescription(): string
    {
        return Arr::get($this->definition, 'description', '');
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return (array)Arr::get($this->definition, 'headers', []);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaName(): string
    {
        $name = (array)Arr::get($this->definition, 'content.application/json.schema', []);
        return key($name);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaObject(): ApiSchemaObjectInterface
    {
        return new OpenApiSchemaObject($this->getSchemaName(), (array)Arr::get($this->definition, 'content.application/json.schema.' . $this->getSchemaName()));
    }

}