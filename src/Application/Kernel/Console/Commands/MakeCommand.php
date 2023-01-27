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

class MakeCommand extends Command
{

    /**
     * @return void
     */

    protected function configure(): void
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

        $name = ucfirst($input->getArgument('name'));

        $util_name = 'Command (' . $name . ')';

        ConsoleUtilities::msgInstalling($util_name, $output);

        try {

            $command_command = preg_split('/(?=[A-Z])/', lcfirst($name));

            foreach ($command_command as $k => $v) {
                $command_command[$k] = strtolower($v);
            }

            $command_command = implode(':', $command_command);

            $src_file = Constants::get('BONES_RESOURCES_PATH') . '/cli/templates/make/command.php';

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Console/Commands/' . $name . '.php');

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_command_name_' => $name,
                '_command_command_' => $command_command,
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($util_name, $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones/blob/master/docs/usage/console.md</info>');

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