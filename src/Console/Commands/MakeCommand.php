<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('make:command')
            ->setDescription('Create a new console command')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of command');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $template = BONES_RESOURCES_PATH . '/cli-templates/command.php';

        if (file_exists($template)) {

            $name = ucfirst($input->getArgument('name'));

            $dir = base_path('/' . strtolower(rtrim(get_config('app.namespace'), '\\')) . '/Console/Commands');

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file_name = $dir . '/' . $name . '.php';

            if (!copy($template, $file_name)) {

                $output->writeln('<error>Unable to create command: Failed to copy file</error>');

                return Command::FAILURE;

            }

            // Edit the contents

            $contents = file_get_contents($file_name);

            $command_command = preg_split('/(?=[A-Z])/', lcfirst($name));

            foreach ($command_command as $k => $v) {
                $command_command[$k] = strtolower($v);
            }

            $command_command = implode(':', $command_command);

            $contents = str_replace([
                '_namespace_',
                '_command_name_',
                '_command_command_',
                '_bones_version_'
            ], [
                rtrim(get_config('app.namespace'), '\\'),
                $name,
                $command_command,
                BONES_VERSION
            ], $contents);

            if (!file_put_contents($file_name, $contents)) {

                unlink($file_name);

                $output->writeLn('<error>Unable to create command: Failed to write file.</error>');

            }

            $output->writeln('<info>Command created at: ' . strtolower(rtrim(get_config('app.namespace'), '\\')) . '/Console/Commands/' . $name . '</info>');

            return Command::SUCCESS;

        } else {

            $output->writeln('<error>Unable to create command: Template not found</error>');

            return Command::FAILURE;

        }

    }


}