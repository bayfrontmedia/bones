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
            ->addOption('value', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
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

        $return_values = $input->getOption('value');

        // Lowercase all as in_array is case-sensitive

        foreach ($return_values as $k => $v) {
            $return_values[$k] = strtolower($v);
        }

        foreach ($filters as $filter => $queued) {

            // Lowercase method as in_array is case-sensitive

            if ((empty($return_values) || in_array(strtolower($filter), $return_values))
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

        $sort = strtolower((string)$input->getOption('sort'));

        if ($sort == 'value') {
            $return = Arr::multisort($return, 'filter');
        } else if ($sort == 'priority') {
            $return = Arr::multisort($return, 'priority');
        } else { // Filter
            $return = Arr::multisort($return, 'function');
        }

        // Return

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

                $table = new Table($output);
                $table->setHeaders(['Filter', 'Value', 'Priority'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }

}