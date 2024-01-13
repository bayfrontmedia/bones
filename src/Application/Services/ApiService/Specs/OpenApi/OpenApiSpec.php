<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiSpecificationException;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiOperationObjectInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSpecificationInterface;

class OpenApiSpec implements ApiSpecificationInterface
{

    protected array $spec;

    /**
     * @param string $json_spec (OpenAPI specification as JSON)
     */
    public function __construct(string $json_spec)
    {
        $this->spec = json_decode($json_spec, true);
    }

    /**
     * Get entire specification array.
     *
     * @return array
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * Get info.
     *
     * @param string $key (In dot notation))
     * @param mixed|null $default
     * @return mixed
     */
    public function getInfo(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->spec, 'info.' . $key, $default);
    }

    /**
     * Get operation object.
     *
     * @param string $path
     * @param string $request_method
     * @return ApiOperationObjectInterface
     * @throws ApiExceptionInterface
     */
    public function getOperationObject(string $path, string $request_method): ApiOperationObjectInterface
    {

        $path = Arr::get($this->spec, 'paths.' . $path . '.' . $request_method);

        if (!$path) {
            throw new ApiSpecificationException('Unable to get path: path does not exist');
        }

        $path = Arr::dot($path);

        foreach ($path as $k => $v) { // Fetch references

            // Populate references

            if (str_ends_with($k, '$ref') && str_starts_with($v, '#/')) {

                $ref_key = ltrim(str_replace([ // Reference key in dot notation
                    '#',
                    '/'
                ], [
                    '',
                    '.'
                ], $v), '.');

                $ref_value = Arr::get($this->spec, $ref_key); // Value of reference

                if (!$ref_value) {
                    throw new ApiSpecificationException('Unable to get path: reference not found');
                }

                $path[$k] = $ref_value;

                $ref_id = explode('.', $ref_key);
                $ref_id = end($ref_id);

                $path = Arr::renameKeys($path, [
                    $k => str_replace('$ref', $ref_id, $k)
                ]);

            }

        }

        return new OpenApiOperationObject(Arr::undot($path));

    }

    /**
     * Get schema.
     *
     * @param string $name (Case-sensitive schema name)
     * @return array
     * @throws ApiExceptionInterface
     */
    public function getSchema(string $name): array
    {

        $schema = Arr::get($this->spec, 'components.schemas.' . $name);

        if (!$schema) {
            throw new ApiSpecificationException('Unable to get schema: schema name does not exist');
        }

        return (array)$schema;

    }

}