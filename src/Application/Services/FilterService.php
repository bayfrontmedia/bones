<?php

namespace Bayfront\Bones\Application\Services;

use Bayfront\Bones\Exceptions\ServiceException;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;
use Bayfront\Hooks\Hooks;

class FilterService
{

    protected $hooks;

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
     * Add filter subscriber.
     *
     * @param FilterSubscriberInterface $subscriber
     * @return void
     * @throws ServiceException
     */

    public function addSubscriber(FilterSubscriberInterface $subscriber)
    {

        $filters = $subscriber->getSubscriptions();

        foreach ($filters as $filter => $subscriptions) {

            // Validate subscriptions

            if (!is_array($subscriptions)) {
                throw new ServiceException('Unable to add filter (' . get_class($subscriber) . '): Invalid subscriptions array');
            }

            foreach ($subscriptions as $subscription) {

                if (!isset($subscription['method'])
                    || !isset($subscription['priority'])) {
                    throw new ServiceException('Unable to add filter (' . get_class($subscriber) . '): Invalid subscription array');
                }

                // Add

                $this->hooks->addFilter($filter, [$subscriber, (string)$subscription['method']], (int)$subscription['priority']);

            }

        }

    }

    /**
     * Execute all subscriptions for a filter in order of priority.
     *
     * @param string $name
     * @param $value
     * @return mixed
     */

    public function doFilter(string $name, $value)
    {
        return $this->hooks->doFilter($name, $value);
    }

}