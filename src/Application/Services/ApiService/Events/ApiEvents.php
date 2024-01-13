<?php

namespace Bayfront\Bones\Application\Services\ApiService\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;

class ApiEvents extends EventSubscriber implements EventSubscriberInterface
{

    /**
     * NOTE:
     * These subscriptions will not show in the filter:list console command,
     * as only locally installed filters are loaded in CLI mode.
     *
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {
        return [

        ];
    }

}