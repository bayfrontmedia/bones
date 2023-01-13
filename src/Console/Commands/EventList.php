<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Hooks\Hooks;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EventList extends Command
{

    protected $hooks;

    public function __construct(Hooks $hooks)
    {

        $this->hooks = $hooks;

        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('event:list')
            ->setDescription('List all hooked events')
            ->addOption('event', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('json', null, InputOption::VALUE_NONE);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $events = $this->hooks->getEvents();

        $return = [];

        $return_events = $input->getOption('event');

        // Lowercase all as in_array is case-sensitive

        foreach ($return_events as $k => $v) {
            $return_events[$k] = strtolower($v);
        }

        foreach ($events as $event => $queued) {

            // Lowercase method as in_array is case-sensitive

            if ((empty($return_events) || in_array(strtolower($event), $return_events))
                && is_array($queued)) {

                foreach ($queued as $queue) {

                    $function = '[' . strtoupper(gettype(Arr::get($queue, 'function'))) . ']';

                    if (is_string(Arr::get($queue, 'function'))) {
                        $function = Arr::get($queue, 'function', '');
                    }

                    $return[] = [
                        'event' => $event,
                        'function' => $function,
                        'priority' => Arr::get($queue, 'priority')
                    ];

                }

            }

        }

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No events found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['event'],
                        $v['function'],
                        $v['priority']
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['Event', 'Function', 'Priority'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }

}