<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Container\Container;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerList extends Command
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

        $this->setName('container:list')
            ->setDescription('List contents of the service container')
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

        $return = [];

        $contents = $this->container->getEntries();
        $aliases = $this->container->getAliases();

        foreach ($contents as $id) {

            $return_alias = '';

            foreach ($aliases as $alias => $class) {

                if ($id == $class) {
                    $return_alias = $alias;
                    break;
                }

            }

            $return[] = [
                'id' => $id,
                'alias' => $return_alias
            ];

        }

        $sort = strtolower((string)$input->getOption('sort'));

        if ($sort == 'alias') {
            $return = Arr::multisort($return, 'alias');
        } else { // id
            $return = Arr::multisort($return, 'id');
        }

        // Return

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No services found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['id'],
                        $v['alias']
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['ID', 'Alias'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;

    }

}