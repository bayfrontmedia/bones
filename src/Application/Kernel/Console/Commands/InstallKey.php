<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Kernel\Console\ConsoleUtilities;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\ConsoleException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallKey extends Command
{

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('install:key')
            ->setDescription('Set the APP_KEY environment variable to a cryptographically secure key if not already existing');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $name = 'APP_KEY';

        ConsoleUtilities::msgEnvAdding($name, $output);

        try {

            $key = App::createKey();

            $dest_file = App::basePath('/.env');

            ConsoleUtilities::replaceFileContents($dest_file, [
                'SECURE_APP_KEY' => $key
            ]);

            ConsoleUtilities::msgEnvInstalled($name, $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/install/manual.md#add-required-environment-variables</info>');

            return Command::SUCCESS;

        } catch (ConsoleException) {
            ConsoleUtilities::msgFailedToWrite($name, $output);
            return Command::FAILURE;
        }

    }

}