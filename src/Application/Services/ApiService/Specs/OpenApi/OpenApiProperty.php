<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiPropertyInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSchemaObjectInterface;

class OpenApiProperty implements ApiPropertyInterface
{

    protected ApiSchemaObjectInterface $apiSchemaObject;

    public function __construct(ApiSchemaObjectInterface $apiSchemaObject)
    {
        $this->apiSchemaObject = $apiSchemaObject;
    }



}