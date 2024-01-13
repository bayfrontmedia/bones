<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseObjectInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiSchemaObjectInterface;

/**
 * See: See: https://swagger.io/specification/#response-object
 */
class OpenApiResponseObject implements ApiResponseObjectInterface
{

    protected array $definition;

    /**
     * @param array $definition
     */
    public function __construct(array $definition)
    {
        $this->definition = $definition;
    }

    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function getDescription(): string
    {
        return Arr::get($this->definition, 'description', '');
    }

    public function getHeaders(): array
    {
        return (array)Arr::get($this->definition, 'headers', []);
    }

    public function getSchemaName(): string
    {
        $name = (array)Arr::get($this->definition, 'content.application/json.schema', []);
        return key($name);
    }

    public function getSchemaObject(): ApiSchemaObjectInterface
    {
        return new OpenApiSchemaObject($this->getSchemaName(), (array)Arr::get($this->definition, 'content.application/json.schema.' . $this->getSchemaName()));
    }


}