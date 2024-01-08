<?php

namespace Bayfront\Bones\Application\Services\Events;

use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\Hooks\Hooks;

class EventService
{

    protected Hooks $hooks;

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
     * Add event subscriptions from an event subscriber.
     *
     * @param EventSubscriberInterface $subscriber
     * @return void
     * @throws ServiceException
     */

    public function addSubscriptions(EventSubscriberInterface $subscriber): void
    {

        $subscriptions = $subscriber->getSubscriptions();

        foreach ($subscriptions as $subscription) {

            // Validate subscriptions

            if (!$subscription instanceof EventSubscription) {
                throw new ServiceException('Unable to add event (' . get_class($subscriber) . '): Invalid event subscription');
            }

            // Add

            $this->hooks->addEvent($subscription->getName(), $subscription->getFunction(), $subscription->getPriority());

        }

    }

    /**
     * Execute all subscriptions for an event in order of priority.
     *
     * @param string $event
     * @param ...$arg
     * @return void
     */

    public function doEvent(string $event, ...$arg): void
    {
        $this->hooks->doEvent($event, ...$arg);
    }

}