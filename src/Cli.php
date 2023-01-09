<?php

namespace Bayfront\Bones;

use Bayfront\StringHelpers\Str;
use Exception;
use League\CLImate\CLImate;

class Cli
{

    protected $cli;

    public function __construct(CLImate $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Show Bones intro and version.
     *
     * @return self
     */

    public function intro(): self
    {

        $this->cli->green('Bones v' . BONES_VERSION)->br();

        return $this;
    }

    /**
     * Start main CLI menu.
     *
     * @return void
     *
     * @throws Exception
     */

    public function start(): void
    {

        $options = [
            '1' => [
                'command' => 'Create a new controller',
                'object' => 'controller'
            ],
            '2' => [
                'command' => 'Create a new exception',
                'object' => 'exception'
            ],
            '3' => [
                'command' => 'Create a new model',
                'object' => 'model'
            ],
            '4' => [
                'command' => 'Create a new service',
                'object' => 'service'
            ],
            '5' => [
                'command' => 'Create a secure key'
            ],
            '6' => [
                'command' => 'Cancel and exit'
            ]
        ];

        $this->cli->out('Please select an option (1-6)')->br();

        foreach ($options as $k => $v) {

            $last_option = $k;

            $this->cli->out('[' . $k . '] ' . $v['command']);

        }

        $this->cli->br();

        $input = $this->cli->input('Please type your choice:');

        $input->accept(array_keys($options));

        $command = $input->prompt();

        if ($command == $last_option) {

            return;

        }

        if ($command == '5') {

            $this->cli->br()->green('Key:')->br()->out(App::createKey())->br();

            $this->start(); // Loop again

            return;

        }

        $input = $this->cli->confirm('Are you sure you want to ' . lcfirst($options[$command]['command'] . '?'));

        if ($input->confirmed()) {

            $input = $this->cli->input('Enter the name for this ' . $options[$command]['object'] . ':');

            $name = $input->prompt();

            $this->cli->br()->out('The name of your ' . $options[$command]['object'] . ' will be: ' . ucfirst(Str::camelCase($name)));

            $this->cli->br()->out('If an existing ' . $options[$command]['object'] . ' exists with the same name, it will be overwritten!');

            $input = $this->cli->confirm('Are you sure?');

            if ($input->confirmed()) {

                /*
                 * $command = controller
                 * $name = name
                 */

                if (file_exists(BONES_RESOURCES_PATH . '/cli-templates/' . $options[$command]['object'] . '.php')) {

                    $dir_name = ucfirst($options[$command]['object']) . 's';

                    $file_name = root_path('/app/' . $dir_name . '/' . ucfirst(Str::camelCase($name)) . '.php');

                    if (!copy(BONES_RESOURCES_PATH . '/cli-templates/' . $options[$command]['object'] . '.php', $file_name)) {

                        $this->cli->red('Error! Unable to create.')->br();

                        $this->start(); // Loop again

                        return;

                    }

                    // Edit the contents

                    $contents = file_get_contents($file_name);

                    $contents = str_replace([
                        $options[$command]['object'] . '_name',
                        'bones_version'
                    ], [
                        ucfirst(Str::camelCase($name)),
                        'v' . BONES_VERSION
                    ], $contents);

                    if (!file_put_contents($file_name, $contents)) {

                        unlink($file_name);

                        $this->cli->red('Error! Unable to write to the file')->br();

                        $this->start(); // Loop again

                        return;

                    }

                    $this->cli->green('Successfully created!')->br();

                    $this->start(); // Loop again

                    return;

                } else {

                    $this->cli->red('Error! Unable to execute command.')->br();

                    $this->start(); // Loop again

                    return;

                }

            } else { // Not confirmed

                $this->cli->br();

                $this->start(); // Loop again

                return;

            }

        } else { // Not confirmed

            $this->cli->br();

            $this->start(); // Loop again

        }

    }

}