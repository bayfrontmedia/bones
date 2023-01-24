<?php

namespace _namespace_\Events;

use Bayfront\Bones\Abstracts\EventSubscriber;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\LabelExistsException;
use Bayfront\CronScheduler\SyntaxException;

/**
 * Actions to perform when the scheduler service exists in the container.
 *
 * Created with Bones v_bones_version_
 */
class ScheduledJobs extends EventSubscriber implements EventSubscriberInterface
{

    protected $scheduler;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Cron $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * @inheritDoc
     */

    public function getSubscriptions(): array
    {
        return [
            'app.cli' => [
                [
                    'method' => 'schedule',
                    'priority' => 5
                ]
            ]
        ];
    }

    /**
     * Add scheduled jobs to scheduler.
     *
     * @return void
     * @throws LabelExistsException
     * @throws SyntaxException
     */

    public function schedule()
    {

        $this->scheduler->call('sample-job', function () {
            sleep(1);
        })->annually();

    }
}