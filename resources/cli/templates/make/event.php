<?php

namespace _namespace_\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;

/**
 * _subscriber_name_ event subscriber.
 *
 * Created with Bones v_bones_version_
 */
class _subscriber_name_ extends EventSubscriber implements EventSubscriberInterface
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
            'app.bootstrap' => [
                [
                    'method' => 'sampleMethod',
                    'priority' => 5
                ]
            ]
        ];
    }

    /**
     * @return void
     */

    public function sampleMethod(): void
    {
        // Do something amazing
    }

}