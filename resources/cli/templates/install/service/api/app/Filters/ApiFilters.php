<?php

namespace _namespace_\Filters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\FilterSubscriber;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;
use Bayfront\PDO\Db;
use Bayfront\Validator\Validate;

/**
 * ApiFilters filter subscriber.
 *
 * Created with Bones v_bones_version_
 */
class ApiFilters extends FilterSubscriber implements FilterSubscriberInterface
{

    protected Db $db;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {

        return [
            'about.bones' => [
                [
                    'method' => 'addApiVersion',
                    'priority' => 5
                ]
            ],
            'api.response' => [
                [
                    'method' => 'addMeta',
                    'priority' => 5
                ]
            ],
            'api.user.password' => [
                [
                    'method' => 'isPasswordAcceptable',
                    'priority' => 5
                ]
            ]
        ];

    }

    /**
     * Add API version.
     *
     * @param array $data
     * @return array
     */
    public function addApiVersion(array $data): array
    {
        return array_merge([
            'API version' => App::getConfig('api.version')
        ], $data);
    }

    /**
     * Add metadata onto returned schema.
     *
     * @param array $schema
     * @return array
     */
    public function addMeta(array $schema): array
    {
        Arr::set($schema, 'meta.api.version', App::getConfig('api.version'));

        if (App::environment() == App::ENV_DEV) {
            Arr::set($schema, 'meta.queries', [
                'count' => $this->db->getTotalQueries(),
                'secs' => $this->db->getQueryTime()
            ]);
        }

        return $schema;
    }

    /**
     * If the password does not meet the minimum requirements,
     * return an empty string. If so, return original password.
     *
     * NOTE: Password should never be logged, stored or recorded in plaintext!
     *
     * @param string $password
     * @return string
     */
    public function isPasswordAcceptable(string $password): string
    {

        /*
         * - Length between 8 and 255 characters
         * - Cannot be an email address
         * - Contains a combination of:
         *     - At least one letter
         *     - At least one number
         *     - At least one special character (including underscores)
         */

        if (!Validate::lengthBetween($password, 8, 255)
            || Validate::email($password)) {
            return '';
        }

        if (preg_match('/[a-zA-Z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/(\W|_)/', $password)) {
            return $password;
        }

        return '';

    }

}