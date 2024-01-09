<?php

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Encryptor\Encryptor;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheSave extends Command
{

    protected Encryptor $encryptor;

    public function __construct(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;

        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('cache:save')
            ->setDescription('Save cache')
            ->addOption('commands', null, InputOption::VALUE_NONE, 'Cache console commands')
            ->addOption('config', null, InputOption::VALUE_NONE, 'Cache config files')
            ->addOption('events', null, InputOption::VALUE_NONE, 'Cache events')
            ->addOption('filters', null, InputOption::VALUE_NONE, 'Cache filters');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $save_all = false;

        if (!$input->getOption('commands')
            && !$input->getOption('config')
            && !$input->getOption('events')
            && !$input->getOption('filters')) {

            $save_all = true;

        }

        // ------------------------- Commands -------------------------

        if ($save_all || $input->getOption('commands')) {

            $output->writeln('<info>Caching console commands...</info>');

            $dir = App::basePath('/app/Console/Commands');

            $classes = [];

            $cache_dest = App::storagePath('/bones/cache/commands.json');

            if (is_dir($dir)) {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $namespace = ltrim(str_replace([
                            '.php',
                            '/'
                        ], [
                            '',
                            '\\'
                        ], str_replace($dir, '', $item->getPathName())), '\\');

                        $classes[] = App::getConfig('app.namespace', '') . 'Console\Commands\\' . $namespace;

                    }

                }

                $cache_dir = dirname($cache_dest);

                if (!is_dir($cache_dir)) {
                    mkdir($cache_dir, 0755, true);
                }

                $success = file_put_contents($cache_dest, json_encode($classes));

                if (!$success) {
                    $output->writeln('<error>Failed to cache console commands: Unable to create file (' . $cache_dest . ') </error>');
                    return Command::FAILURE;
                }

            } else if (is_file($cache_dest)) {
                unlink($cache_dest);
            }

            $output->writeln('<info>' . count($classes) . ' console commands cached successfully!</info>');

        }

        // ------------------------- Config -------------------------

        if ($save_all || $input->getOption('config')) {

            $output->writeln('<info>Caching config files...</info>');

            $dir = App::configPath();

            $config = [];

            $cache_dest = App::storagePath('/bones/cache/config');

            if (is_dir($dir)) {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $filename = str_replace('.php', '', basename($item->getPathName()));

                        $contents = require($item->getPathName());

                        if (is_array($contents)) { // Valid format
                            $config[$filename] = $contents;
                        }

                    }

                }

                $cache_dir = dirname($cache_dest);

                if (!is_dir($cache_dir)) {
                    mkdir($cache_dir, 0755, true);
                }

                $success = file_put_contents($cache_dest, $this->encryptor->encryptString(json_encode($config)));

                if (!$success) {
                    $output->writeln('<error>Failed to cache config files: Unable to create file (' . $cache_dest . ') </error>');
                    return Command::FAILURE;
                }

            } else if (is_file($cache_dest)) {
                unlink($cache_dest);
            }

            $output->writeln('<info>' . count($config) . ' config files cached successfully!</info>');

        }

        // ------------------------- Events -------------------------

        if ($save_all || $input->getOption('events')) {

            $output->writeln('<info>Caching events...</info>');

            $dir = App::basePath('/app/Events');

            $classes = [];

            $cache_dest = App::storagePath('/bones/cache/events.json');

            if (is_dir($dir)) {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $namespace = ltrim(str_replace([
                            '.php',
                            '/'
                        ], [
                            '',
                            '\\'
                        ], str_replace($dir, '', $item->getPathName())), '\\');

                        $classes[] = App::getConfig('app.namespace', '') . 'Events\\' . $namespace;

                    }

                }

                $cache_dir = dirname($cache_dest);

                if (!is_dir($cache_dir)) {
                    mkdir($cache_dir, 0755, true);
                }

                $success = file_put_contents($cache_dest, json_encode($classes));

                if (!$success) {
                    $output->writeln('<error>Failed to cache events: Unable to create file (' . $cache_dest . ') </error>');
                    return Command::FAILURE;
                }

            } else if (is_file($cache_dest)) {
                unlink($cache_dest);
            }

            $output->writeln('<info>' . count($classes) . ' events cached successfully!</info>');

        }

        // ------------------------- Filters -------------------------

        if ($save_all || $input->getOption('filters')) {

            $output->writeln('<info>Caching filters...</info>');

            $dir = App::basePath('/app/Filters');

            $classes = [];

            $cache_dest = App::storagePath('/bones/cache/filters.json');

            if (is_dir($dir)) {

                $list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($list as $item) {

                    if ($item->isFile() && $item->getExtension() == 'php') {

                        $namespace = ltrim(str_replace([
                            '.php',
                            '/'
                        ], [
                            '',
                            '\\'
                        ], str_replace($dir, '', $item->getPathName())), '\\');

                        $classes[] = App::getConfig('app.namespace', '') . 'Filters\\' . $namespace;

                    }

                }

                $cache_dir = dirname($cache_dest);

                if (!is_dir($cache_dir)) {
                    mkdir($cache_dir, 0755, true);
                }

                $success = file_put_contents($cache_dest, json_encode($classes));

                if (!$success) {
                    $output->writeln('<error>Failed to cache filters: Unable to create file (' . $cache_dest . ') </error>');
                    return Command::FAILURE;
                }

            } else if (is_file($cache_dest)) {
                unlink($cache_dest);
            }

            $output->writeln('<info>' . count($classes) . ' filters cached successfully!</info>');

        }

        return Command::SUCCESS;

    }


}