<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Interfaces\FilterInterface;
use Bayfront\Hooks\Hooks;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilterList extends Command
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

        $this->setName('filter:list')
            ->setDescription('List all registered filters')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
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

        $filters = $this->hooks->getFilters();

        $return = [];

        $return_filters = $input->getOption('filter');

        // Lowercase all as in_array is case-sensitive

        foreach ($return_filters as $k => $v) {
            $return_filters[$k] = strtolower($v);
        }

        foreach ($filters as $filter => $queued) {

            // Lowercase method as in_array is case-sensitive

            if ((empty($return_filters) || in_array(strtolower($filter), $return_filters))
                && is_array($queued)) {

                foreach ($queued as $queue) {

                    $function = '[' . strtoupper(gettype(Arr::get($queue, 'function'))) . ']';

                    if (is_string(Arr::get($queue, 'function'))) {

                        $function = Arr::get($queue, 'function', '');

                    } else if (is_array(Arr::get($queue, 'function'))) {

                        if (Arr::get($queue, 'function')[0] instanceof FilterInterface) {

                            $function = get_class(Arr::get($queue, 'function')[0]);

                        }

                    }

                    $return[] = [
                        'function' => $function,
                        'filter' => $filter,
                        'priority' => Arr::get($queue, 'priority')
                    ];

                }

            }

        }

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No filters found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['function'],
                        $v['filter'],
                        $v['priority']
                    ];

                }

                $sort = strtolower((string)$input->getOption('sort'));

                if ($sort == 'filter') {
                    $rows = Arr::multisort($rows, '1');
                } else if ($sort == 'priority') {
                    $rows = Arr::multisort($rows, '2');
                } else { // Function
                    $rows = Arr::multisort($rows, '0');
                }

                $table = new Table($output);
                $table->setHeaders(['Action', 'Filter', 'Priority'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }

}