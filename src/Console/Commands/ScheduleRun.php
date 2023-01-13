<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\Container\NotFoundException;
use Bayfront\CronScheduler\Cron;
use Bayfront\CronScheduler\FilesystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleRun extends Command
{

    /** @var Cron  */

    protected $schedule;

    public function __construct(Cron $schedule)
    {

        $this->schedule = $schedule;

        parent::__construct();

    }

    protected function configure()
    {

        $this->setName('schedule:run')
            ->setDescription('Run scheduled tasks');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws FilesystemException
     * @throws NotFoundException
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        do_event('app.schedule.start');

        $result = $this->schedule->run();

        do_event('app.schedule.end', $result);

        return Command::SUCCESS;

    }



}