<?php

namespace Bayfront\Bones\Interfaces;

interface FilterSubscriberInterface
{

    /**
     * Filter(s) to subscribe to in which the key = filter name
     * and value is an array of subscriptions in which each contains
     * the keys "method" (to execute) and "priority" (int)
     *
     * [
     *   'app.bootstrap' => [
     *     [
     *       'method' => 'methodName',
     *       'priority' => 5
     *     ],
     *     [
     *       'method'  => 'anotherMethodName',
     *       'priority' => 5
     *     ]
     *   ]
     * ]
     *
     * @return array
     */

    public function getSubscriptions(): array;

}