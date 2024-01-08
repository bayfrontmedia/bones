<?php

namespace Bayfront\Bones\Application\Services\Filters;

use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;
use Bayfront\Hooks\Hooks;

class FilterService
{

    protected Hooks $hooks;

    public function __construct(Hooks $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * Return an array of all filter subscriptions.
     * @return array
     */

    public function getSubscriptions(): array
    {
        return $this->hooks->getFilters();
    }

    /**
     * Add filter subscriptions from a filter subscriber.
     *
     * @param FilterSubscriberInterface $subscriber
     * @return void
     * @throws ServiceException
     */

    public function addSubscriptions(FilterSubscriberInterface $subscriber): void
    {

        $subscriptions = $subscriber->getSubscriptions();

        foreach ($subscriptions as $subscription) {

            // Validate subscriptions

            if (!$subscription instanceof FilterSubscription) {
                throw new ServiceException('Unable to add filter (' . get_class($subscriber) . '): Invalid filter subscription');
            }

            // Add

            $this->hooks->addFilter($subscription->getName(), $subscription->getFunction(), $subscription->getPriority());

        }

    }

    /**
     * Execute all subscriptions for a filter in order of priority.
     *
     * @param string $name
     * @param $value
     * @return mixed
     */

    public function doFilter(string $name, $value): mixed
    {
        return $this->hooks->doFilter($name, $value);
    }

}