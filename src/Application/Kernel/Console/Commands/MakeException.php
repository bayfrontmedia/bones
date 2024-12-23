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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeException extends Command
{

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('make:exception')
            ->setDescription('Create a new exception')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of exception');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $name = ucfirst($input->getArgument('name'));

        $util_name = 'Exception (' . $name . ')';

        ConsoleUtilities::msgInstalling($util_name, $output);

        try {

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/make/exception.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Exceptions/' . $name . '.php');

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_exception_name_' => $name,
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($util_name, $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/usage/exceptions.md</info>');

            return Command::SUCCESS;

        } catch (FileAlreadyExistsException) {
            ConsoleUtilities::msgFileExists($util_name, $output);
            return Command::FAILURE;
        } catch (UnableToCopyException) {
            ConsoleUtilities::msgUnableToCopy($util_name, $output);
            return Command::FAILURE;
        } catch (ConsoleException) {
            ConsoleUtilities::msgFailedToWrite($util_name, $output);
            return Command::FAILURE;
        }

    }

}