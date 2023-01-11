<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\Bones\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyCreate extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('key:create')
            ->setDescription('Create a cryptographically secure key');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln(App::createKey());

        return Command::SUCCESS;
    }


}