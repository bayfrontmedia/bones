<?php

namespace Bayfront\Bones\Application\Kernel\Console;

use Bayfront\Bones\Exceptions\ConsoleException;
use Bayfront\Bones\Exceptions\FileAlreadyExistsException;
use Bayfront\Bones\Exceptions\UnableToCopyException;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleUtilities
{

    /**
     * Copy file.
     *
     * @param string $src
     * @param string $dest
     * @return void
     * @throws UnableToCopyException
     * @throws FileAlreadyExistsException
     */

    public static function copyFile(string $src, string $dest): void
    {

        $dir = dirname($dest);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($dest)) {
            throw new FileAlreadyExistsException('Unable to copy: file already exists (' . basename($dest) . ')');
        }

        if (!copy($src, $dest)) {
            throw new UnableToCopyException('Unable to copy file (' . basename($dest) . ')');
        }

    }

    /**
     * Replace file contents.
     *
     * @param string $src
     * @param array $replacements
     * @return void
     * @throws ConsoleException
     */

    public static function replaceFileContents(string $src, array $replacements): void
    {

        $contents = file_get_contents($src);

        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        if (!file_put_contents($src, $contents)) {
            throw new ConsoleException('Unable to write to file (' . basename($src) . ')');
        }

    }

    /*
     * |--------------------------------------------------------------------------
     * | Messages
     * |--------------------------------------------------------------------------
     */

    // ------------------------- Plain -------------------------

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgInstalling(string $name, OutputInterface $output): void
    {
        $output->writeln('Installing ' . $name . '...');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgEnvAdding(string $name, OutputInterface $output): void
    {
        $output->writeln('Adding ' . $name . ' to .env...');
    }

    // ------------------------- Info -------------------------

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgInstalled(string $name, OutputInterface $output): void
    {
        $output->writeln('<info>' . ucfirst($name) . ' successfully installed!</info>');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgEnvInstalled(string $name, OutputInterface $output): void
    {
        $output->writeln('<info>' . ucfirst($name) . ' successfully added to .env!</info>');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgInstallComplete(string $name, OutputInterface $output): void
    {
        $output->writeln('<info>' . ucfirst($name) . ' installation complete!</info>');
    }

    // ------------------------- Errors -------------------------

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgEnvExists(string $name, OutputInterface $output): void
    {
        $output->writeln('<error>Unable to add ' . $name . ' to .env: One or more variables already exist</error>');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgEnvFailedToWrite(string $name, OutputInterface $output): void
    {
        $output->writeln('<error>Unable to add ' . $name . ' to .env: Check permissions and try again.</error>');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgFileExists(string $name, OutputInterface $output): void
    {
        $output->writeln('<error>Skipping ' . $name . ': File already exists</error>');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgUnableToCopy(string $name, OutputInterface $output): void
    {
        $output->writeln('<error>Unable to copy ' . $name . ': Check permissions and try again.</error>');
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     * @return void
     */

    public static function msgFailedToWrite(string $name, OutputInterface $output): void
    {
        $output->writeln('<error>Unable to write to ' . $name . ' file: Check permissions and try again.</error>');
    }

}