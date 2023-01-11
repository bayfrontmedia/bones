<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\Bones\Cli;
use Bayfront\Container\ContainerException;
use Exception;
use League\CLImate\CLImate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command
{

    protected function configure()
    {

        $this->setName('create')
            ->setDescription('Create resources')
            ->setHelp('Creates resources to be used in your app.');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws ContainerException
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        /** @var CLImate $cli */

        $climate = set_in_container('cli', 'League\CLImate\CLImate');

        $cli = new Cli($climate);

        $cli->intro()->start();

        return Command::SUCCESS;

    }



}