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
            ->setDescription('List all hooked filters')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
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
                    }

                    $return[] = [
                        'filter' => $filter,
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
                $output->writeln('<info>No filters found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['filter'],
                        $v['function'],
                        $v['priority']
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['Filter', 'Function', 'Priority'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }

}