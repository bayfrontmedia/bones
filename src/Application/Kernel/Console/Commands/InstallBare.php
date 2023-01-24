<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Kernel\Console\ConsoleUtilities;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Application\Utilities\Constants;
use Bayfront\Bones\Exceptions\ConsoleException;
use Bayfront\Bones\Exceptions\FileAlreadyExistsException;
use Bayfront\Bones\Exceptions\UnableToCopyException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
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
            ->addOption('router', null, InputOption::VALUE_NONE, 'Install router')
            ->addOption('scheduler', null, InputOption::VALUE_NONE, 'Install scheduler')
            ->addOption('veil', null, InputOption::VALUE_NONE, 'Install Veil');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     * @throws ExceptionInterface
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        ConsoleUtilities::msgInstalling('Bones (bare)', $output);

        // ------------------------- .env -------------------------

        try {

            $name = '.env file';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/bare/.env';

            $dest_file = App::basePath('/.env');

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                'SECURE_APP_KEY' => App::createKey()
            ]);

            ConsoleUtilities::msgInstalled($name, $output);

        } catch (FileAlreadyExistsException $e) {
            ConsoleUtilities::msgFileExists($name, $output);
        } catch (UnableToCopyException $e) {
            ConsoleUtilities::msgUnableToCopy($name, $output);
        } catch (ConsoleException $e) {
            ConsoleUtilities::msgFailedToWrite($name, $output);
        }

        // ------------------------- Default exception handler -------------------------

        try {

            $name = 'Exception handler';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/bare/app/Exceptions/Handler.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Exceptions') . '/Handler.php';

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($name, $output);

        } catch (FileAlreadyExistsException $e) {
            ConsoleUtilities::msgFileExists($name, $output);
        } catch (UnableToCopyException $e) {
            ConsoleUtilities::msgUnableToCopy($name, $output);
        } catch (ConsoleException $e) {
            ConsoleUtilities::msgFailedToWrite($name, $output);
        }

        // Errors controller

        $name = 'Errors controller';

        ConsoleUtilities::msgInstalling($name, $output);

        $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/bare/app/Controllers/Errors.php';

        $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Controllers/Errors.php');

        try {

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($name, $output);

        } catch (FileAlreadyExistsException $e) {
            ConsoleUtilities::msgFileExists($name, $output);
        } catch (UnableToCopyException $e) {
            ConsoleUtilities::msgUnableToCopy($name, $output);
        } catch (ConsoleException $e) {
            ConsoleUtilities::msgFailedToWrite($name, $output);
        }

        // ------------------------- Default event subscriber(s) -------------------------

        // Bootstrap

        try {

            $name = 'Bootstrap events';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/bare/app/Events/Bootstrap.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Events') . '/Bootstrap.php';

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($name, $output);

        } catch (FileAlreadyExistsException $e) {
            ConsoleUtilities::msgFileExists($name, $output);
        } catch (UnableToCopyException $e) {
            ConsoleUtilities::msgUnableToCopy($name, $output);
        } catch (ConsoleException $e) {
            ConsoleUtilities::msgFailedToWrite($name, $output);
        }

        // Dev

        try {

            $name = 'Dev events';

            ConsoleUtilities::msgInstalling($name, $output);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/install/bare/app/Events/Dev.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Events') . '/Dev.php';

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($name, $output);

        } catch (FileAlreadyExistsException $e) {
            ConsoleUtilities::msgFileExists($name, $output);
        } catch (UnableToCopyException $e) {
            ConsoleUtilities::msgUnableToCopy($name, $output);
        } catch (ConsoleException $e) {
            ConsoleUtilities::msgFailedToWrite($name, $output);
        }

        /*
         * |--------------------------------------------------------------------------
         * | Optional services
         * |--------------------------------------------------------------------------
         */

        ConsoleUtilities::msgInstalling('optional services', $output);

        // ------------------------- Db (optional) -------------------------

        if ($input->getOption('db')) {

            // See: https://symfony.com/doc/current/console/calling_commands.html

            $command = $this->getApplication()->find('install:service');

            $arguments = [
                '--db' => true,
            ];

            $db_input = new ArrayInput($arguments);
            $command->run($db_input, $output);

        }

        // ------------------------- Filesystem (optional) -------------------------

        if ($input->getOption('filesystem')) {

            $command = $this->getApplication()->find('install:service');

            $arguments = [
                '--filesystem' => true,
            ];

            $fs_input = new ArrayInput($arguments);
            $command->run($fs_input, $output);

        }

        // ------------------------- Logs (optional) -------------------------

        if ($input->getOption('logs')) {

            $command = $this->getApplication()->find('install:service');

            $arguments = [
                '--logs' => true,
            ];

            $db_input = new ArrayInput($arguments);
            $command->run($db_input, $output);

        }

        // ------------------------- Router -------------------------

        if ($input->getOption('router')) {

            $command = $this->getApplication()->find('install:service');

            $arguments = [
                '--router' => true,
            ];

            $router_input = new ArrayInput($arguments);
            $command->run($router_input, $output);

        }

        // ------------------------- Scheduler -------------------------

        if ($input->getOption('scheduler')) {

            $command = $this->getApplication()->find('install:service');

            $arguments = [
                '--scheduler' => true,
            ];

            $scheduler_input = new ArrayInput($arguments);
            $command->run($scheduler_input, $output);

        }

        // ------------------------- Veil -------------------------

        if ($input->getOption('veil')) {

            $command = $this->getApplication()->find('install:service');

            $arguments = [
                '--veil' => true,
            ];

            $scheduler_input = new ArrayInput($arguments);
            $command->run($scheduler_input, $output);

        }

        // ------------------------- Composer -------------------------

        ConsoleUtilities::msgInstalling('dependencies', $output);
        shell_exec('composer update');

        //$output->writeln('<info>NOTE: It is recommended to update dependencies using "composer update"</info>');
        $output->writeln('<info>Bones installation complete! (v' . App::getBonesVersion() . ')</info>');
        $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/README.md</info>');

        return Command::SUCCESS;

    }

}