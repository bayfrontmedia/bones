<?php

namespace Bayfront\Bones\Interfaces;

interface FilterInterface
{

    /**
     * Is the filter active?
     *
     * @return bool
     */

    public function isActive(): bool;

    /**
     * Filter(s) to subscribe to in which the key = filter name and value = priority (int)
     *
     * @return array
     */

    public function getFilters(): array;

    /**
     * Filter action.
     */

    public function action($value);

}