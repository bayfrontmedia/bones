<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiPropertyInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiSchemaObjectInterface;

class OpenApiProperty implements ApiPropertyInterface
{

    protected ApiSchemaObjectInterface $apiSchemaObject;

    public function __construct(ApiSchemaObjectInterface $apiSchemaObject)
    {
        $this->apiSchemaObject = $apiSchemaObject;
    }



}