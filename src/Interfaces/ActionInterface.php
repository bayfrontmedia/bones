<?php

namespace Bayfront\Bones\Interfaces;

interface ActionInterface
{

    /**
     * Is the action active?
     *
     * @return bool
     */

    public function isActive(): bool;

    /**
     * Event(s) to subscribe to in which the key = event name and value = priority (int)
     *
     * @return array
     */

    public function getEvents(): array;

    /**
     * Action to add.
     */

    public function action(...$arg);

}