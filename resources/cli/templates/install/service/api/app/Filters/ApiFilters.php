<?php

namespace _namespace_\Filters;

use Bayfront\Bones\Abstracts\FilterSubscriber;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;
use Bayfront\Validator\Validate;

/**
 * ApiFilters filter subscriber.
 *
 * Created with Bones v_bones_version_
 */
class ApiFilters extends FilterSubscriber implements FilterSubscriberInterface
{

    /**
     * The container will resolve any dependencies.
     */

    public function __construct()
    {

    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): array
    {

        return [
            'api.user.password' => [
                [
                    'method' => 'isPasswordAcceptable',
                    'priority' => 5
                ]
            ]
        ];

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