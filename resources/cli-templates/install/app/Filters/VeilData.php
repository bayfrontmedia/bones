<?php

namespace App\Filters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Filter;
use Bayfront\Bones\Interfaces\FilterInterface;

/**
 * VeilData filter.
 *
 * Add info to the Veil data array.
 */
class VeilData extends Filter implements FilterInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return $this->container->has('Bayfront\Veil\Veil');
    }

    /**
     * @inheritDoc
     */

    public function getFilters(): array
    {

        return [
            'veil.data' => 5
        ];
    }

    /**
     *
     * @inheritDoc
     *
     */

    public function action($value): array
    {

        $merge = [
            'app' => [
                // Config values
                'debug_mode' => get_config('app.debug_mode'),
                'environment' => get_config('app.environment')
            ],
            'bones' => [
                'version' => BONES_VERSION
            ]
        ];

        /*
         * Recursively merge the two arrays, allowing values defined
         * in the controller to overwrite the default values specified here.
         */

        return Arr::undot(array_merge(Arr::dot($merge), Arr::dot($value)));

    }

}