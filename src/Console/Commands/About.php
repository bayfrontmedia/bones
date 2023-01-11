<?php

namespace Bayfront\Bones\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class About extends Command
{

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('about')
            ->setDescription('Retrieve information about this Bones application')
            ->addOption('json', null, InputOption::VALUE_NONE);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $about = [
            'Bones version' => BONES_VERSION,
            'PHP version' => phpversion(),
            'Timezone' => date_default_timezone_get()
        ];

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($about));
        } else {
            foreach ($about as $k => $v) {
                $output->writeLn($k . ': ' . $v);
            }
        }

        return Command::SUCCESS;
    }


}