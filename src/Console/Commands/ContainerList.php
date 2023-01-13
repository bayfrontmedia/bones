<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\Container\Container;
use Exception;
use Symfony\Component\Console\Command\Command;
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

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {
            foreach ($return as $v) {
                $output->writeLn($v);
            }
        }

        return Command::SUCCESS;
    }


}