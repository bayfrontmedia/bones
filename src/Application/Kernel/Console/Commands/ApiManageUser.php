<?php /** @noinspection DuplicatedCode */

namespace Bayfront\Bones\Application\Kernel\Console\Commands;

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Models\Relationships\UserTenantsModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ApiManageUser extends Command
{

    protected UsersModel $usersModel;
    protected UserTenantsModel $userTenantsModel;

    public function __construct(UsersModel $usersModel, UserTenantsModel $userTenantsModel)
    {
        $this->usersModel = $usersModel;
        $this->userTenantsModel = $userTenantsModel;

        parent::__construct();
    }

    /*
     * Options
     */
    private const OPTION_CREATE = 'Create a new user';
    private const OPTION_ENABLE = 'Enable user';
    private const OPTION_DISABLE = 'Disable user';
    private const OPTION_DELETE = 'Delete user';

    /**
     * @return void
     */
    protected function configure(): void
    {

        $this->setName('api:manage:user')
            ->setDescription('Manage API user');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Are you sure you want to manage an API user? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Choose an option: ', [
            self::OPTION_CREATE,
            self::OPTION_ENABLE,
            self::OPTION_DISABLE,
            self::OPTION_DELETE
        ],
            0
        );

        $question->setErrorMessage('Option %s is invalid.');
        $option = $helper->ask($input, $output, $question);

        if ($option == self::OPTION_CREATE) {
            return $this->createUser($input, $output);
        } else if ($option == self::OPTION_ENABLE) {
            return $this->enableUser($input, $output);
        } else if ($option == self::OPTION_DISABLE) {
            return $this->disableUser($input, $output);
        } else if ($option == self::OPTION_DELETE) {
            return $this->deleteUser($input, $output);
        } else {
            $output->writeln('<error>Unknown option</error>');
            return Command::FAILURE;
        }

    }

    /**
     * Create new user.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function createUser(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter email: ');
        $email = $helper->ask($input, $output, $question);

        $question = new Question('Enter password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        $question = new Question('Re-enter password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password2 = $helper->ask($input, $output, $question);

        if ($password !== $password2) {

            $output->writeln('<error>Unable to create user: Passwords do not match</error>');
            return Command::FAILURE;

        }

        $meta = [];

        foreach (App::getConfig('api.required_meta.users') as $k => $v) {

            $question = new Question('Enter required meta (' . $k . '): ');
            $meta[$k] = $helper->ask($input, $output, $question);

        }

        $enabled = false;

        $question = new ConfirmationQuestion('Enable user? [y/n] ', false);

        if ($helper->ask($input, $output, $question)) {
            $enabled = true;
        }

        $output->writeLn('<info>Ready to create user:</info>');
        $output->writeLn('<question>Email:</question> ' . $email);
        $output->writeLn('<question>Password:</question> (Hidden)');

        foreach ($meta as $k => $v) {
            $output->writeLn('<question>Meta (' . $k . '):</question> ' . $v);
        }

        if ($enabled) {
            $output->writeLn('<question>Enabled:</question> True');
        } else {
            $output->writeLn('<question>Enabled:</question> False');
        }

        $question = new ConfirmationQuestion('Are you sure you want to proceed? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        // Create user

        $attrs = [
            'email' => $email,
            'password' => $password,
            'enabled' => $enabled
        ];

        if (!empty($meta)) {
            $attrs['meta'] = $meta;
        }

        try {

            $user_id = $this->usersModel->create($attrs);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>User successfully created!</info>');
        $output->writeLn('<info>ID: ' . $user_id . '</info>');

        return Command::SUCCESS;

    }

    /**
     * Enable user.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function enableUser(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter user ID: ');
        $user_id = $helper->ask($input, $output, $question);

        try {

            $user = $this->usersModel->get($user_id, [
                'email'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to enable this user (' . $user['email'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {

            $this->usersModel->update($user_id, [
                'enabled' => true
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>User (' . $user['email'] . ') successfully enabled</info>');
        return Command::SUCCESS;

    }

    /**
     * Disable user.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function disableUser(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter user ID: ');
        $user_id = $helper->ask($input, $output, $question);

        try {

            $user = $this->usersModel->get($user_id, [
                'email'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to disable this user (' . $user['email'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {

            $this->usersModel->update($user_id, [
                'enabled' => false
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>User (' . $user['email'] . ') successfully disabled</info>');
        return Command::SUCCESS;

    }

    /**
     * Delete user.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private function deleteUser(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $question = new Question('Enter user ID: ');
        $user_id = $helper->ask($input, $output, $question);

        try {

            $user = $this->usersModel->get($user_id, [
                'email'
            ]);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $question = new ConfirmationQuestion('Are you sure you want to delete this user (' . $user['email'] . ')? [y/n] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        try {

            $this->usersModel->delete($user_id);

        } catch (Exception $e) {

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;

        }

        $output->writeLn('<info>User (' . $user['email'] . ') successfully deleted</info>');
        return Command::SUCCESS;

    }

}