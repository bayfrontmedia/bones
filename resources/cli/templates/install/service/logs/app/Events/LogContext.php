<?php

namespace _namespace_\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\HttpRequest\Request;
use Bayfront\MonologFactory\Exceptions\ChannelNotFoundException;
use Bayfront\MonologFactory\LoggerFactory;

/**
 * Actions to perform when the logs service exists in the container.
 *
 * Created with Bones v_bones_version_
 */
class LogContext extends EventSubscriber implements EventSubscriberInterface
{

    protected $logs;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(LoggerFactory $logs)
    {
        $this->logs = $logs;
    }

    /**
     * @inheritDoc
     */

    public function getSubscriptions(): array
    {
        return [
            'app.http' => [
                [
                    'method' => 'addExtraLogContext',
                    'priority' => 1
                ]
            ]
        ];
    }

    /**
     * Adds IP and URL to all log entries.
     *
     * NOTE: This method cannot subscribe to events outside the HTTP interface.
     *
     * @return void
     * @throws ChannelNotFoundException
     */

    public function addExtraLogContext()
    {

        $channels = $this->logs->getChannels();

        foreach ($channels as $channel) {

            $this->logs->getChannel($channel)->pushProcessor(function ($record) {

                $record['extra']['ip'] = Request::getIp();
                $record['extra']['url'] = Request::getUrl(true);

                return $record;

            });

        }

    }
}