<?php

namespace Bayfront\Bones\Application\Services;

use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\Hooks\Hooks;

class EventService
{

    protected $hooks;

    public function __construct(Hooks $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * Return an array of all event subscriptions.
     * @return array
     */

    public function getSubscriptions(): array
    {
        return $this->hooks->getEvents();
    }

    /**
     * Add event subscriber.
     *
     * @throws ServiceException
     */

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {

        $events = $subscriber->getSubscriptions();

        foreach ($events as $event => $subscriptions) {

            // Validate subscriptions

            if (!is_array($subscriptions)) {
                throw new ServiceException('Unable to add event (' . get_class($subscriber) . '): Invalid subscriptions array');
            }

            foreach ($subscriptions as $subscription) {

                if (!isset($subscription['method'])
                    || !isset($subscription['priority'])) {
                    throw new ServiceException('Unable to add event (' . get_class($subscriber) . '): Invalid subscription array');
                }

                // Add

                $this->hooks->addEvent($event, [$subscriber, (string)$subscription['method']], (int)$subscription['priority']);

            }

        }

    }

    /**
     * Execute all subscriptions for an event in order of priority.
     *
     * @param string $event
     * @param ...$arg
     * @return void
     */

    public function doEvent(string $event, ...$arg)
    {
        $this->hooks->doEvent($event, ...$arg);
    }

}