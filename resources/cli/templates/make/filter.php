<?php

namespace _namespace_\Filters;

use Bayfront\Bones\Abstracts\FilterSubscriber;
use Bayfront\Bones\Application\Services\Filters\FilterSubscription;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;

/**
 * _filter_name_ filter subscriber.
 *
 * Created with Bones v_bones_version_
 */
class _filter_name_ extends FilterSubscriber implements FilterSubscriberInterface
{

    /**
     * The container will resolve any dependencies.
     */

    public function __construct()
    {

    }

    /**
     * @inheritDoc
     */

    public function getSubscriptions(): array
    {

        return [
            new FilterSubscription('filter.name', [$this, 'capitalizeString'], 10)
        ];
        
    }

    /**
     * @param string $orig
     * @return string
     */

    public function capitalizeString(string $orig): string
    {
        return strtoupper($orig);
    }

}