<?php

namespace Bayfront\Bones\Application\Services\ApiService\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSchemaInterface;

class OpenApiSchema implements ApiSchemaInterface
{

    protected array $schema;

    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    public function getRequiredProperties(): array
    {
        return Arr::get($this->schema, 'required', []);
    }

    public function isRequired(string $property): bool
    {
        return in_array($property, $this->getRequiredProperties());
    }



}