<?php

namespace Bayfront\Bones\Services\Api\Schemas;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\InvalidSchemaException;
use Bayfront\ArraySchema\SchemaInterface;

class AuthResource implements SchemaInterface
{

    /**
     * @inheritDoc
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
            'user_id',
            'access_token',
            'refresh_token',
            'expires_in',
            'expires_at'
        ])) {
            throw new InvalidSchemaException('Unable to create AuthResource schema: missing required keys');
        }

        return [
            'data' => [
                'type' => 'token',
                'id' => date('c'),
                'attributes' => [
                    'userId' => $array['user_id'],
                    'accessToken' => $array['access_token'],
                    'refreshToken' => $array['refresh_token'],
                    'type' => 'Bearer',
                    'expiresIn' => $array['expires_in'],
                    'expiresAt' => $array['expires_at']
                ]
            ]
        ];

    }
}