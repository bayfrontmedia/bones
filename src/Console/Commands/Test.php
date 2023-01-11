<?php

namespace Bayfront\Bones\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command
{

    protected function configure()
    {

        $this->setName('test')
            ->setDescription('Prints Hello-World!')
            ->setHelp('Demonstration of custom commands created by Symfony Console component.')
            ->addArgument('username', InputArgument::REQUIRED, 'Pass the username.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln(sprintf('Hello World!, %s', $input->getArgument('username')));

        return Command::SUCCESS;
    }



}