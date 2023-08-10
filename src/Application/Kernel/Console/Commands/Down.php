<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Down extends Command
{

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('down')
            ->setDescription('Put Bones into maintenance mode')
            ->addOption('allow', null, InputOption::VALUE_REQUIRED, 'Comma-separated IP\'s to allow')
            ->addOption('message', null, InputOption::VALUE_REQUIRED, 'Message to be returned');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $json = [];

        $allow = $input->getArgument('allow');

        if ($allow) {

            $allow = explode(',', $allow);

            // Sanitize
            foreach ($allow as $k => $v) {
                $allow[$k] = trim($v);
            }

            $json['allow'] = $allow;

        }

        $message = $input->getArgument('message');

        if ($message) {
            $json['message'] = $message;
        }

        $dest = App::storagePath('/bones/down.json');

        $dir = dirname($dest);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $success = file_put_contents($dest, json_encode($json));

        if (!$success) {
            $output->writeln('<error>Failed to enter maintenance mode: Unable to create file (' . $dest . ') </error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Successfully entered maintenance mode!</info>');
        return Command::SUCCESS;

    }

}