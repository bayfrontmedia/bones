<?php

namespace App\Actions;

use Bayfront\Bones\Action;
use Bayfront\Bones\Interfaces\ActionInterface;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\LabelExistsException;
use Bayfront\CronScheduler\SyntaxException;

/**
 * ScheduleJobs action.
 */
class ScheduleJobs extends Action implements ActionInterface
{

    protected $schedule;

    public function __construct(Cron $schedule)
    {

        $this->schedule = $schedule;

        parent::__construct();

    }

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
     * @throws LabelExistsException
     * @throws SyntaxException
     */

    public function action(...$arg)
    {

        // Add jobs

        $this->schedule->call('sample-job', function () {
            sleep(1);
        })->annually();

    }

}