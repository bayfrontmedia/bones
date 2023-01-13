<?php

namespace Bayfront\Bones\Console\Commands;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\RouteIt\Router;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RouteList extends Command
{

    protected $router;

    public function __construct(Router $router)
    {

        $this->router = $router;

        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure()
    {

        $this->setName('route:list')
            ->setDescription('List all routes')
            ->addOption('method', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('host', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
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

        $routes = $this->router->getRoutes();

        $return = [];

        $return_methods = $input->getOption('method');
        $return_hosts = $input->getOption('host');

        // Lowercase all as in_array is case-sensitive

        foreach ($return_methods as $k => $v) {
            $return_methods[$k] = strtolower($v);
        }

        foreach ($routes as $method => $hosts) {

            // Rename key assigned by Route It library

            if ($method == 'named_routes') {
                $method = 'named';
            }

            // Lowercase method as in_array is case-sensitive

            if ((empty($return_methods) || in_array(strtolower($method), $return_methods))
                && is_array($hosts)) {

                foreach ($hosts as $host => $paths) {

                    if (empty($return_hosts) || in_array($host, $return_hosts)
                        && is_array($paths)) {

                        foreach ($paths as $path => $route) {

                            $name = '';

                            if (is_array($route)) {
                                $name = Arr::get($route, 'name', '');
                            }

                            $destination = '[' . strtoupper(gettype(Arr::get($route, 'destination'))) . ']';

                            if (is_string(Arr::get($route, 'destination'))) {
                                $destination = Arr::get($route, 'destination', '');
                            }

                            $return[] = [
                                'method' => $method,
                                'host' => $host,
                                'path' => $path,
                                'name' => $name,
                                'destination' => $destination
                            ];

                        }

                    }

                }

            }

        }

        if ($input->getOption('json')) {
            $output->writeLn(json_encode($return));
        } else {

            if (empty($return)) {
                $output->writeln('<info>No routes found.</info>');
            } else {

                $rows = [];

                foreach ($return as $v) {

                    $rows[] = [
                        $v['method'],
                        $v['host'],
                        $v['path'],
                        $v['name'],
                        $v['destination']
                    ];

                }

                $table = new Table($output);
                $table->setHeaders(['Request method', 'Host', 'Path', 'Name', 'Destination'])->setRows($rows);
                $table->render();

            }

        }

        return Command::SUCCESS;
    }

}