<?php

namespace Bayfront\Bones\Interfaces;

interface EventSubscriberInterface
{

    /**
     * Array of EventSubscription instances.
     *
     * [
     *   new EventSubscription('event.name', [$this, 'methodName'], 10),
     * ]
     *
     * @return array
     */

    public function getSubscriptions(): array;

}