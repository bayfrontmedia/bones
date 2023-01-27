<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Interfaces\EventSubscriberInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EventList extends Command
{

    protected EventService $events;

    public function __construct(EventService $events)
    {
        $this->events = $events;
        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('event:list')
            ->setDescription('List all event subscriptions')
            ->addOption('event', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('sort', null, InputOption::VALUE_REQUIRED)
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

        $subscriptions = $this->events->getSubscriptions();

        $return = [];

        $return_subscriptions = $input->getOption('event');

        // Lowercase all as in_array is case-sensitive

        foreach ($return_subscriptions as $k => $v) {
            $return_subscriptions[$k] = strtolower($v);
        }

        foreach ($subscriptions as $event => $subscription) {

            // Lowercase method as in_array is case-sensitive

            if ((empty($return_subscriptions) || in_array(strtolower($event), $return_subscriptions))
                && is_array($subscription)) {

                foreach ($subscription as $queue) {

                    $function = '[' . strtoupper(gettype(Arr::get($queue, 'function'))) . ']';

                    if (is_string(Arr::get($queue, 'function'))) {

                        $function = Arr::get($queue, 'function', '');

                    } else if (is_array(Arr::get($queue, 'function'))) {

                        if (Arr::get($queue, 'function')[0] instanceof EventSubscriberInterface) {

                            $function = get_class(Arr::get($queue, 'function')[0]) . '::' . Arr::get($queue, 'function')[1];

                        }

                    }

                    $return[] = [
                        'subscriber' => $function,
                        'event' => $event,
                        'priority' => Arr::get($queue, 'priority')
                    ];

                }

            }

        }

        // Sort

        $sort = strtolower((string)$input->getOption('sort'));

        if ($sort == 'event') {
            $return = Arr::multisort($return, 'event');
        } else if ($sort == 'priority') {
            $return = Arr::multisort($return, 'priority');
        } else { // Subscriber
            $return = Arr::multisort($return, 'subscriber');
        }

        // Return

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No event subscriptions found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['subscriber'],
                        $v['event'],
                        $v['priority']
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['Subscriber', 'Event', 'Priority'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;

    }

}