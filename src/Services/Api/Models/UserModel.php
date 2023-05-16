<?php

namespace Bayfront\Bones\Services\Api\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Models\Abstracts\ApiModel;
use Bayfront\Bones\Services\Api\Models\Resources\UserMetaModel;
use Bayfront\Bones\Services\Api\Models\Resources\UsersModel;
use Bayfront\PDO\Db;
use Monolog\Logger;

/**
 * User is verified to exist in constructor.
 */
class UserModel extends ApiModel
{

    protected UsersModel $usersModel;
    protected UserMetaModel $userMetaModel;

    private string $user_id;
    private array $user;

    /**
     * @param EventService $events
     * @param Db $db
     * @param Logger $log
     * @param UsersModel $usersModel
     * @param UserMetaModel $userMetaModel
     * @param string $user_id
     * @param bool $skip_log (Skip api.user.read logs and events - used when a user authenticates)
     * @throws NotFoundException
     */
    public function __construct(EventService $events, Db $db, Logger $log, UsersModel $usersModel, UserMetaModel $userMetaModel, string $user_id, bool $skip_log = false)
    {
        $this->usersModel = $usersModel;
        $this->userMetaModel = $userMetaModel;

        $this->user_id = $user_id;

        $this->user = Arr::except($this->usersModel->getEntire($this->getId(), $skip_log), [
            'password'
        ]);

        parent::__construct($events, $db, $log);
    }

    // ------------------------- User -------------------------

    public function getId(): string
    {
        return $this->user_id;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function get(string $key, $default = null): mixed
    {
        return Arr::get($this->getUser(), $key, $default);
    }

    public function isEnabled(): bool
    {
        return $this->get('enabled', false);
    }
    
    // -------------------------User meta -------------------------

    /*
     * TODO:
     * Can remove UserMetaModel if not needed here,
     * although it will most likely be used often by the app.
     */

    private static array $meta = [];

    public function getMetaValue(string $id): mixed
    {

        if (isset(self::$meta[$id])) {
            return self::$meta[$id];
        }

        self::$meta[$id] = $this->userMetaModel->getValue($this->getId(), $id);

        return self::$meta[$id];

    }





}