<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\FilesystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleRun extends Command
{

    protected Cron $scheduler;
    protected EventService $events;

    public function __construct(Cron $scheduler, EventService $events)
    {

        $this->scheduler = $scheduler;
        $this->events = $events;

        parent::__construct();
    }

    protected function configure(): void
    {

        $this->setName('schedule:run')
            ->setDescription('Run all scheduled jobs which are due');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws FilesystemException
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        if (App::isDown()) {
            $output->writeln('<error>Unable to run scheduled jobs while Bones is down.</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Begin running scheduled jobs...</info>');

        $this->events->doEvent('app.schedule.start', $this->scheduler);

        $result = $this->scheduler->run();

        $this->events->doEvent('app.schedule.end', $result);

        $output->writeln('<info>Completed running ' . Arr::get($result, 'count', '0') . ' scheduled jobs (took ' . Arr::get($result, 'elapsed', '0') . ' secs).</info>');

        return Command::SUCCESS;

    }

}