<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAction extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('make:action')
            ->setDescription('Create a new action')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of action');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $template = BONES_RESOURCES_PATH . '/cli-templates/action.php';

        if (file_exists($template)) {

            $name = ucfirst($input->getArgument('name'));

            $dir = base_path('/' . strtolower(rtrim(get_config('app.namespace'), '\\')) . '/Actions');

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file_name = $dir . '/' . $name . '.php';

            if (!copy($template, $file_name)) {

                $output->writeln('<error>Unable to create action: Failed to copy file</error>');

                return Command::FAILURE;

            }

            // Edit the contents

            $contents = file_get_contents($file_name);

            $contents = str_replace([
                '_namespace_',
                '_action_name_',
                '_bones_version_'
            ], [
                rtrim(get_config('app.namespace'), '\\'),
                $name,
                BONES_VERSION
            ], $contents);

            if (!file_put_contents($file_name, $contents)) {

                unlink($file_name);

                $output->writeLn('<error>Unable to create action: Failed to write file.</error>');

            }

            $output->writeln('<info>Action created at: ' . strtolower(rtrim(get_config('app.namespace'), '\\')) . '/Actions/' . $name . '</info>');

            return Command::SUCCESS;

        } else {

            $output->writeln('<error>Unable to create action: Template not found</error>');

            return Command::FAILURE;

        }

    }


}