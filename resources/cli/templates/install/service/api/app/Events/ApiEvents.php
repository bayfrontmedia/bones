<?php

namespace _namespace_\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\CronScheduler\Cron;
use Monolog\Logger;

/**
 * ApiEvents event subscriber.
 *
 * Created with Bones v_bones_version_
 */
class ApiEvents extends EventSubscriber implements EventSubscriberInterface
{

    protected Cron $scheduler;
    protected Logger $log;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Cron $scheduler, Logger $log)
    {
        $this->scheduler = $scheduler;
        $this->log = $log;
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {

        return [
            'app.cli' => [
                [
                    'method' => 'deleteExpiredBuckets',
                    'priority' => 5
                ],
                [
                    'method' => 'deleteExpiredInvitations',
                    'priority' => 5
                ],
                [
                    'method' => 'deleteExpiredUserKeys',
                    'priority' => 5
                ]
            ],
            'api.auth' => [ // TODO: Not yet created event
                [
                    'method' => 'addUserIdToLogs',
                    'priority' => 5
                ]
            ]
        ];

    }

    public function deleteExpiredBuckets(): void
    {
        // TODO
    }

    public function deleteExpiredInvitations(): void
    {
        // TODO
    }

    public function deleteExpiredUserKeys(): void
    {
        // TODO
    }

    private string $user_id;

    public function addUserIdToLogs(string $user_id): void
    {

        $this->user_id = $user_id;

        $this->log->pushProcessor(function ($record) {

            $record['extra']['user_id'] = $this->user_id;

            return $record;

        });

    }

}