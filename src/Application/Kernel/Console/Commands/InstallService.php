<?php /** @noinspection DuplicatedCode */

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Kernel\Console\ConsoleUtilities;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Application\Utilities\Constants;
use Bayfront\Bones\Exceptions\ConsoleException;
use Bayfront\Bones\Exceptions\FileAlreadyExistsException;
use Bayfront\Bones\Exceptions\UnableToCopyException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallService extends Command
{

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('install:service')
            ->setDescription('Install an optional service')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Install API service')
            ->addOption('db', null, InputOption::VALUE_NONE, 'Install database')
            ->addOption('router', null, InputOption::VALUE_NONE, 'Install router')
            ->addOption('scheduler', null, InputOption::VALUE_NONE, 'Install scheduler')
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

        // ------------------------- API (optional) -------------------------

        if ($input->getOption('api')) {

            ConsoleUtilities::msgInstalling('API service', $output);

            if (!App::has('db')
                || !App::has('router')
                || !App::has('scheduler')
                || !App::has('Monolog\Logger')) {

                $output->writeln('<error>Unable to install API service: Dependant services are missing</error>');

                return Command::FAILURE;

            }

            // API config

            try {

                $name = 'API config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/api/config/api.php';

                $dest_file = App::configPath('/api.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            // API docs

            try {

                $name = 'API docs config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/api/config/api-docs.php';

                $dest_file = App::configPath('/api-docs.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            // API plans

            try {

                $name = 'API plans config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/api/config/api-plans.php';

                $dest_file = App::configPath('/api-plans.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            // Database migrations

            try {

                $name = 'Database migrations';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/api/resources/database/migrations/2023-05-26-203030_create_api_tables.php';

                $dest_file = App::resourcesPath('/database/migrations/2023-05-26-203030_create_api_tables.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            // API events

            $name = 'API events';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/api/app/Events/ApiEvents.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Events/ApiEvents.php');

            try {

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::replaceFileContents($dest_file, [
                    '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                    '_bones_version_' => App::getBonesVersion()
                ]);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            } catch (ConsoleException) {
                ConsoleUtilities::msgFailedToWrite($name, $output);
            }

            // API filters

            $name = 'API filters';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/api/app/Filters/ApiFilters.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Filters/ApiFilters.php');

            try {

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::replaceFileContents($dest_file, [
                    '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                    '_bones_version_' => App::getBonesVersion()
                ]);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            } catch (ConsoleException) {
                ConsoleUtilities::msgFailedToWrite($name, $output);
            }

            // Run migrations

            $output->writeln('Running database migrations...');

            shell_exec('composer update');
            shell_exec('php bones migrate:up');

            ConsoleUtilities::msgInstallComplete('API service', $output);

            $output->writeln('<info>*** NOTE: Additional steps are required to complete the API service installation! ***</info>');
            $output->writeln('<info>- If using Bones controllers, ensure the class_namespace router config key is blank</info>');
            $output->writeln('<info>- Ensure the exception handler class extends ApiExceptionHandler</info>');
            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/services/api/README.md</info>');

        }

        // ------------------------- Db (optional) -------------------------

        if ($input->getOption('db')) {

            ConsoleUtilities::msgInstalling('Database service', $output);

            // Database config

            try {

                $name = 'Database config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/db/config/database.php';

                $dest_file = App::configPath('/database.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                // Add PDO extension to Composer

                //shell_exec('composer require ext-pdo');

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            // .env

            $name = 'database variables';

            ConsoleUtilities::msgEnvAdding($name, $output);

            $vars = [
                'DB_ADAPTER',
                'DB_HOST',
                'DB_PORT',
                'DB_DATABASE',
                'DB_USER',
                'DB_PASSWORD',
                'DB_SECURE_TRANSPORT'
            ];

            $var_exists = false;

            foreach ($vars as $var) {

                if (App::envHas($var)) {
                    $var_exists = true;
                }

            }

            if ($var_exists) {

                ConsoleUtilities::msgEnvExists($name, $output);

            } else {

                $env_db_contents = file_get_contents(Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/db/.env.example');

                if (!file_put_contents(App::basePath('/.env'), $env_db_contents, FILE_APPEND)) {

                    ConsoleUtilities::msgEnvFailedToWrite($name, $output);

                } else {

                    ConsoleUtilities::msgEnvInstalled($name, $output);

                }

            }

            ConsoleUtilities::msgInstallComplete('Database service', $output);

            $output->writeln('<info>*** NOTE: Be sure to update .env with your database credentials! ***</info>');
            $output->writeln('<info>*** NOTE: It is recommended to update Composer using "composer require ext-pdo" ***</info>');
            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/services/db.md</info>');

        }

        // ------------------------- Router (optional) -------------------------

        if ($input->getOption('router')) {

            ConsoleUtilities::msgInstalling('Router service', $output);

            // Router config

            try {

                $name = 'Router config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/router/config/router.php';

                $dest_file = App::configPath('/router.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            // .env

            $name = 'router variables';

            ConsoleUtilities::msgEnvAdding($name, $output);

            $vars = [
                'ROUTER_HOST',
                'ROUTER_ROUTE_PREFIX'
            ];

            $var_exists = false;

            foreach ($vars as $var) {

                if (App::envHas($var)) {
                    $var_exists = true;
                }

            }

            if ($var_exists) {

                ConsoleUtilities::msgEnvExists($name, $output);

            } else {

                $env_router_contents = file_get_contents(Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/router/.env.example');

                if (!file_put_contents(App::basePath('/.env'), $env_router_contents, FILE_APPEND)) {

                    ConsoleUtilities::msgEnvFailedToWrite($name, $output);

                } else {

                    ConsoleUtilities::msgEnvInstalled($name, $output);

                }

            }

            // Routes event

            $name = 'Routes event';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/router/app/Events/Routes.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Events/Routes.php');

            try {

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::replaceFileContents($dest_file, [
                    '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                    '_bones_version_' => App::getBonesVersion()
                ]);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            } catch (ConsoleException) {
                ConsoleUtilities::msgFailedToWrite($name, $output);
            }

            // Home controller

            $name = 'Home controller';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/router/app/Controllers/Home.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Controllers/Home.php');

            try {

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::replaceFileContents($dest_file, [
                    '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                    '_bones_version_' => App::getBonesVersion()
                ]);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            } catch (ConsoleException) {
                ConsoleUtilities::msgFailedToWrite($name, $output);
            }

            ConsoleUtilities::msgInstallComplete('Router service', $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/services/router.md</info>');

        }

        // ------------------------- Scheduler (optional) -------------------------

        if ($input->getOption('scheduler')) {

            ConsoleUtilities::msgInstalling('Scheduler service', $output);

            try {

                $name = 'Scheduler config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/scheduler/config/scheduler.php';

                $dest_file = App::configPath('/scheduler.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            try {

                $name = 'ScheduledJobs event';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/scheduler/app/Events/ScheduledJobs.php';

                $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Events') . '/ScheduledJobs.php';

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::replaceFileContents($dest_file, [
                    '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                    '_bones_version_' => App::getBonesVersion()
                ]);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            } catch (ConsoleException) {
                ConsoleUtilities::msgFailedToWrite($name, $output);
            }

            ConsoleUtilities::msgInstallComplete('Scheduler service', $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/services/scheduler.md</info>');

        }

        // ------------------------- Veil (optional) -------------------------

        if ($input->getOption('veil')) {

            ConsoleUtilities::msgInstalling('Veil service', $output);

            try {

                $name = 'Veil config';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/veil/config/veil.php';

                $dest_file = App::configPath('/veil.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            try {

                $name = 'Veil view: footer';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/veil/resources/views/examples/layouts/partials/footer.veil.php';

                $dest_file = App::resourcesPath('/views/examples/layouts/partials/footer.veil.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            try {

                $name = 'Veil view: head';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/veil/resources/views/examples/layouts/partials/head.veil.php';

                $dest_file = App::resourcesPath('/views/examples/layouts/partials/head.veil.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            try {

                $name = 'Veil view: page';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/veil/resources/views/examples/layouts/container.veil.php';

                $dest_file = App::resourcesPath('/views/examples/layouts/container.veil.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            try {

                $name = 'Veil view: home';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/veil/resources/views/examples/pages/home.veil.php';

                $dest_file = App::resourcesPath('/views/examples/pages/home.veil.php');

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            try {

                $name = 'Veil example controller';

                ConsoleUtilities::msgInstalling($name, $output);

                $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/service/veil/app/Controllers/VeilExample.php';

                $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Controllers') . '/VeilExample.php';

                ConsoleUtilities::copyFile($src_file, $dest_file);

                ConsoleUtilities::replaceFileContents($dest_file, [
                    '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                    '_bones_version_' => App::getBonesVersion()
                ]);

                ConsoleUtilities::msgInstalled($name, $output);

            } catch (FileAlreadyExistsException) {
                ConsoleUtilities::msgFileExists($name, $output);
            } catch (UnableToCopyException) {
                ConsoleUtilities::msgUnableToCopy($name, $output);
            }

            ConsoleUtilities::msgInstallComplete('Veil service', $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/services/veil.md</info>');

        }

        return Command::SUCCESS;

    }

}