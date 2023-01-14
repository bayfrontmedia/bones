<?php

namespace _namespace_\Filters;

use Bayfront\Bones\Filter;
use Bayfront\Bones\Interfaces\FilterInterface;

/**
 * _filter_name_ filter.
 *
 * Created for Bones v_bones_version_
 */
class _filter_name_ extends Filter implements FilterInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */

    public function getFilters(): array
    {

        return [
            'filter.name' => 5
        ];
    }

    /**
     * @inheritDoc
     */

    public function action($value)
    {
        return $value;
    }

}