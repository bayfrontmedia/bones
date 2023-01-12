<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeService extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('make:service')
            ->setDescription('Create a new service')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of service');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $template = BONES_RESOURCES_PATH . '/cli-templates/service.php';

        if (file_exists($template)) {

            $name = ucfirst($input->getArgument('name'));

            $dir = base_path('/' . strtolower(rtrim(get_config('app.namespace'), '\\')) . '/Services');

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file_name = $dir . '/' . $name . '.php';

            if (!copy($template, $file_name)) {

                $output->writeln('Unable to create service: Failed to copy file');

                return Command::FAILURE;

            }

            // Edit the contents

            $contents = file_get_contents($file_name);

            $contents = str_replace([
                '_namespace_',
                '_service_name_',
                '_bones_version_'
            ], [
                rtrim(get_config('app.namespace'), '\\'),
                $name,
                BONES_VERSION
            ], $contents);

            if (!file_put_contents($file_name, $contents)) {

                unlink($file_name);

                $output->writeLn('Unable to create service: Failed to write file.');

            }

            $output->writeln('Service created at: ' . strtolower(rtrim(get_config('app.namespace'), '\\')) . '/Services/' . $name);

            return Command::SUCCESS;

        } else {

            $output->writeln('Unable to create service: Template not found');

            return Command::FAILURE;

        }

    }


}