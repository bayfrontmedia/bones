<?php

namespace Bayfront\Bones\Application\Services\ApiService\Interfaces\Specs;

interface ApiSchemaObjectInterface
{

    /**
     * Get entire schema object definition.
     *
     * @return array
     */
    public function getDefinition(): array;

    /**
     * Get schema name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get all schema properties.
     *
     * @return array
     */
    public function getProperties(): array;

    /**
     * Get array of required property names.
     *
     * @return array
     */
    public function getRequiredProperties(): array;

    /**
     * Is property name required?
     *
     * @param string $property
     * @return bool
     */
    public function isRequired(string $property): bool;

}