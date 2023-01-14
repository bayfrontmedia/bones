<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\App;
use Bayfront\Container\Container;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AliasList extends Command
{

    protected $container;

    public function __construct(Container $container)
    {

        $this->container = $container;

        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('alias:list')
            ->setDescription('List all registered aliases')
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

        $aliases = App::getAliases();

        $return = [];

        foreach ($aliases as $id => $class) {

            $return[] = [
                'alias' => $id,
                'class' => $class,
                'in_container' => $this->container->has($class)
            ];

        }

        $sort = strtolower((string)$input->getOption('sort'));

        if ($sort == 'class') {
            $return = Arr::multisort($return, 'class');
        } else if ($sort == 'used') {
            $return = Arr::multisort($return, 'in_container');
        } else { // Alias
            $return = Arr::multisort($return, 'alias');
        }

        // Return

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No aliases found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['alias'],
                        $v['class'],
                        $v['in_container'] ? 'Yes' : 'No'
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['Alias', 'Class', 'Used'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }

}