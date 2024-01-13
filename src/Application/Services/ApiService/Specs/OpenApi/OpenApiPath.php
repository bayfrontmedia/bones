<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiSpecificationException;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiPathInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiResponseInterface;

/**
 * See: https://swagger.io/specification/#operation-object
 */
class OpenApiPath implements ApiPathInterface
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

    /*
     * TODO:
     * - parameters (interface)
     * - requestBody.content (interface)
     */

    /**
     * @inheritDoc
     */
    public function getResponseObject(string $name): ApiResponseInterface
    {

        if (!Arr::has($this->definition, 'responses.' . $name)) {
            throw new ApiSpecificationException('Unable to get response object: name does not exist');
        }

        return new OpenApiResponse(Arr::get($this->definition, 'responses.' . $name));

    }

}