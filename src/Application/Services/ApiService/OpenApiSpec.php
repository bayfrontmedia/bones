<?php

namespace Bayfront\Bones\Application\Services\ApiService;

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
     * @return array
     * @throws InvalidArgumentException
     */
    public function getPath(string $path): array
    {

        $path = Arr::get($this->spec, 'paths.' . $path);

        if (!$path) {
            throw new InvalidArgumentException('Unable to get path: path does not exist');
        }

        return $path;

    }

}