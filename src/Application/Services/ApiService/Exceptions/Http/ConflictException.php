<?php

namespace Bayfront\Bones\Application\Services\ApiService\Exceptions\Http;

use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiException;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

/**
 * HTTP status 409.
 */
class ConflictException extends ApiException implements ApiExceptionInterface
{

    /**
     * @inheritDoc
     */
    public function getHttpStatusCode(): int
    {
        return 409;
    }

}