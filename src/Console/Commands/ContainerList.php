<?php

namespace Bayfront\Bones\Console\Commands;

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
            ->setDescription('List contents of services container')
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

        $return = $this->container->getContents();

        ksort($return); // Sort

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No services found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['ID'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }


}