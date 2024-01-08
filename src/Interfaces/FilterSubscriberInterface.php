<?php

namespace Bayfront\Bones\Interfaces;

interface FilterSubscriberInterface
{

    /**
     * Array of FilterSubscription instances.
     *
     * [
     *   new FilterSubscription('filter.name', [$this, 'methodName'], 10),
     * ]
     *
     * @return array
     */

    public function getSubscriptions(): array;

}