<?php

namespace Bayfront\Bones\Application\Services\ApiService\OpenApi;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiSpecificationInterface;
use Bayfront\Bones\Exceptions\InvalidArgumentException;

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
     * Get path.
     *
     * @param string $path
     * @param string|null $request_method
     * @return array
     * @throws InvalidArgumentException
     */
    public function getPath(string $path, ?string $request_method = null): array
    {

        if ($request_method) {
            $path = $path . '.' . $request_method;
        }

        $path = Arr::get($this->spec, 'paths.' . $path);

        if (!$path) {
            throw new InvalidArgumentException('Unable to get path: path does not exist');
        }

        $path = Arr::dot($path);

        foreach ($path as $k => $v) { // Fetch references

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
                    throw new InvalidArgumentException('Unable to get path: reference not found');
                }

                $path[$k] = $ref_value;

                $ref_id = explode('.', $ref_key);
                $ref_id = end($ref_id);

                $path = Arr::renameKeys($path, [
                    $k => str_replace('$ref', $ref_id, $k)
                ]);

            }

        }

        return Arr::undot($path);

    }

    /**
     * Get schema.
     *
     * @param string $schema (Case-sensitive schema name)
     * @return array
     * @throws InvalidArgumentException
     */
    public function getSchema(string $schema): array
    {

        $schema = Arr::get($this->spec, 'components.schemas.' . $schema);

        if (!$schema) {
            throw new InvalidArgumentException('Unable to get schema: schema does not exist');
        }

        return (array)$schema;

    }

}