<?php

namespace App\Actions;

use Bayfront\Bones\Action;
use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\Container\NotFoundException;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\LabelExistsException;
use Bayfront\CronScheduler\SyntaxException;

/**
 * ScheduleJobs action.
 */
class ScheduleJobs extends Action implements ActionInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */

    public function getEvents(): array
    {

        return [
            'app.cli' => 5
        ];
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws LabelExistsException
     * @throws SyntaxException
     */

    public function action(...$arg)
    {

        /** @var Cron $schedule */
        $schedule = $this->container->get('Bayfront\CronScheduler\Cron');

        // Add jobs

        $schedule->call('sample-job', function () {
            sleep(1);
        })->annually();

    }

}