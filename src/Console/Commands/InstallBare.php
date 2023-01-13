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
            ->addOption('filesystem', null, InputOption::VALUE_NONE, 'Install filesystem')
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

        $output->writeln('Installing Bones...');

        // ------------------------- .env -------------------------

        $output->writeln('Creating .env file...');

        $bones_env_file = BONES_RESOURCES_PATH . '/cli-templates/install/.env';

        $bones_env_contents = file_get_contents($bones_env_file);

        // Create key

        $bones_env_contents = str_replace('SECURE_APP_KEY', create_key(), $bones_env_contents);

        $app_env_file = base_path('/.env');

        if (!file_exists($app_env_file)) {

            file_put_contents($app_env_file, $bones_env_contents);

        } else {
            $output->writeln('<error>Skipping .env file: File already exists</error>');
        }

        // ------------------------- db -------------------------

        if ($input->getOption('db')) {

            $output->writeln('Creating database config...');

            $bones_db_file = BONES_RESOURCES_PATH . '/cli-templates/install/config/database.php';

            $app_db_file = config_path('/database.php');

            if (!file_exists($app_db_file)) {

                copy($bones_db_file, $app_db_file);

            } else {
                $output->writeln('<error>Skipping database config file: File already exists</error>');
            }

            $output->writeln('Adding database variables to .env...');

            $add_db_env_contents = file_get_contents(BONES_RESOURCES_PATH . '/cli-templates/install/.env-db');

            if (!file_put_contents($app_env_file, $add_db_env_contents, FILE_APPEND)) {

                $output->writeln('<error>Unable to add database variables to .env!</error>');

            }

        }

        // ------------------------- Filesystem -------------------------

        if ($input->getOption('filesystem')) {

            $output->writeln('Installing filesystem config...');

            $bones_filesystem_file = BONES_RESOURCES_PATH . '/cli-templates/install/config/filesystem.php';

            $app_filesystem_file = config_path('/filesystem.php');

            if (!file_exists($app_filesystem_file)) {

                copy($bones_filesystem_file, $app_filesystem_file);

            } else {
                $output->writeln('<error>Skipping filesystem config file: File already exists</error>');
            }

        }

        // ------------------------- Logs -------------------------

        if ($input->getOption('logs')) {

            $output->writeln('Installing logs config...');

            $bones_logs_file = BONES_RESOURCES_PATH . '/cli-templates/install/config/logs.php';

            $app_logs_file = config_path('/logs.php');

            if (!file_exists($app_logs_file)) {

                copy($bones_logs_file, $app_logs_file);

            } else {
                $output->writeln('<error>Skipping logs config file: File already exists</error>');
            }

        }

        // ------------------------- Translate -------------------------

        if ($input->getOption('translation')) {

            $output->writeln('Installing translation config...');

            $bones_translation_file = BONES_RESOURCES_PATH . '/cli-templates/install/config/translation.php';

            $app_translation_file = config_path('/translation.php');

            if (!file_exists($app_translation_file)) {

                copy($bones_translation_file, $app_translation_file);

            } else {
                $output->writeln('<error>Skipping translation config file: File already exists</error>');
            }

            $output->writeln('Installing sample translations...');

            if (!is_dir(resources_path('/translations/en')) && !is_dir(resources_path('/translations/es'))) {

                mkdir(resources_path('/translations/en'), 0755, true);
                mkdir(resources_path('/translations/es'), 0755, true);

                copy(BONES_RESOURCES_PATH . '/cli-templates/install/resources/translations/en/common.php', resources_path('/translations/en/common.php'));
                copy(BONES_RESOURCES_PATH . '/cli-templates/install/resources/translations/es/common.php', resources_path('/translations/es/common.php'));

            } else {
                $output->writeln('<error>Skipping sample translations: Directory already exists</error>');
            }

        }

        // ------------------------- Veil -------------------------

        if ($input->getOption('veil')) {

            $output->writeln('Installing Veil config...');

            $bones_veil_file = BONES_RESOURCES_PATH . '/cli-templates/install/config/veil.php';

            $app_veil_file = config_path('/veil.php');

            if (!file_exists($app_veil_file)) {

                copy($bones_veil_file, $app_veil_file);

            } else {
                $output->writeln('<error>Skipping Veil config file: File already exists</error>');
            }

        }

        $output->writeln('Installing dependencies...');

        exec('composer install');

        $output->writeln('<info>Bones installation complete!</info>');

        return Command::SUCCESS;

    }

}