<?php

namespace Bayfront\Bones\Services\Api\Schemas;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\ArraySchema\SchemaInterface;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;

class AuthResource implements SchemaInterface
{

    /**
     * @inheritDoc
     * @throws UnexpectedApiException
     */
    public static function create(array $array, array $config = []): array
    {

        if (Arr::isMissing($array, [
            'access_token',
            'refresh_token',
            'expires_in',
            'expires_at'
        ])) {
            throw new UnexpectedApiException('Unable to create AuthResource schema: missing required keys');
        }

        return [
            'data' => [
                'type' => 'token',
                'id' => date('c'),
                'attributes' => [
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