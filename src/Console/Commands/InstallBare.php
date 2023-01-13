<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallBare extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('install:bare')
            ->setDescription('Install Bones (bare)')
            ->addOption('db', null, InputOption::VALUE_NONE, 'Install database')
            ->addOption('logs', null, InputOption::VALUE_NONE, 'Install logs')
            ->addOption('translation', null, InputOption::VALUE_NONE, 'Install translation')
            ->addOption('veil', null, InputOption::VALUE_NONE, 'Install Veil');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // .env

        $bones_env = BONES_RESOURCES_PATH . '/cli-templates/install/.env';
        $env_contents = file_get_contents($bones_env);

        if ($env_contents) {

            $env_contents = str_replace('SECURE_APP_KEY', create_key(), $env_contents);

            $app_env = base_path('/.env2');

            if (file_exists($app_env)) {

                $output->writeln('Unable to install: File already exists');

                return Command::FAILURE;

            }

            if (!file_put_contents($app_env, $env_contents)) {

                $output->writeln('Unable to install: Failed to copy file');

                return Command::FAILURE;

            }

            $output->writeln('Continue...');

            // db

            if ($input->getOption('db')) {

                $output->writeln('Installing database...');

                $bones_db = BONES_RESOURCES_PATH . '/cli-templates/install/config/database.php';

                $app_db = config_path('/database.php');

                if (file_exists($app_db)) {

                    $output->writeln('Unable to install: File already exists');

                    return Command::FAILURE;

                }
                if (!copy($bones_db, $app_db)) {

                    $output->writeln('Unable to install: Failed to copy file');

                    return Command::FAILURE;

                }

                $add_env_contents = file_get_contents(BONES_RESOURCES_PATH . '/cli-templates/install/.env-db');

                if ($add_env_contents) {

                    if (!file_put_contents($app_env, $add_env_contents, FILE_APPEND)) {

                        $output->writeln('Unable to install: Failed to append to file');

                        return Command::FAILURE;

                    }

                } else {

                    $output->writeln('Unable to install: Required file missing');

                    return Command::FAILURE;

                }

            }

            /*
             * Revisit logs after update
             */

            if ($input->getOption('logs')) {

                $output->writeln('Installing logs...');

                $bones_logs = BONES_RESOURCES_PATH . '/cli-templates/install/config/logs.php';

                $app_logs = config_path('/logs2.php');

                if (file_exists($app_logs)) {

                    $output->writeln('Unable to install: File already exists');

                    return Command::FAILURE;

                }
                if (!copy($bones_logs, $app_logs)) {

                    $output->writeln('Unable to install: Failed to copy file');

                    return Command::FAILURE;

                }

            }

            // Translate

            if ($input->getOption('translation')) {

                $output->writeln('Installing translation...');

                $bones_translation = BONES_RESOURCES_PATH . '/cli-templates/install/config/translation.php';

                $app_translation = config_path('/translation2.php');

                if (file_exists($app_translation)) {

                    $output->writeln('Unable to install: File already exists');

                    return Command::FAILURE;

                }
                if (!copy($bones_translation, $app_translation)) {

                    $output->writeln('Unable to install: Failed to copy file');

                    return Command::FAILURE;

                }

                if (!is_dir(resources_path('/translations/en'))) {
                    mkdir(resources_path('/translations/en'), 0755, true);
                }

                if (!is_dir(resources_path('/translations/es'))) {
                    mkdir(resources_path('/translations/es'), 0755, true);
                }

                copy(BONES_RESOURCES_PATH . '/cli-templates/install/resources/translations/en/common.php', resources_path('/translations/en/common.php'));
                copy(BONES_RESOURCES_PATH . '/cli-templates/install/resources/translations/es/common.php', resources_path('/translations/es/common.php'));

            }

            // Veil

            if ($input->getOption('veil')) {

                $output->writeln('Installing Veil...');

                $bones_veil = BONES_RESOURCES_PATH . '/cli-templates/install/config/veil.php';

                $app_veil = config_path('/veil2.php');

                if (file_exists($app_veil)) {

                    $output->writeln('Unable to install: File already exists');

                    return Command::FAILURE;

                }

                if (!copy($bones_veil, $app_veil)) {

                    $output->writeln('Unable to install: Failed to copy file');

                    return Command::FAILURE;

                }

            }

            /*
             * TODO: Filesystem:
             * Add after update.
             */

            $output->writeln('Installing dependencies...');

            //exec('composer install');

            $output->writeln('Installation successful!');


            return Command::SUCCESS;

        } else {

            $output->writeln('Unable to install: Template not found');

            return Command::FAILURE;

        }

    }

}