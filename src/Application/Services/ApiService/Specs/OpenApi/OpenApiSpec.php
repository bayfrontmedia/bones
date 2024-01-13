<?php

namespace Bayfront\Bones\Application\Services\ApiService\Specs\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiSpecificationException;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiPathInterface;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs\ApiSpecInterface;

class OpenApiSpec implements ApiSpecInterface
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
     * @inheritDoc
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * @inheritDoc
     */
    public function getInfo(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->spec, 'info.' . $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function getPath(string $path, string $request_method): ApiPathInterface
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
     * @inheritDoc
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