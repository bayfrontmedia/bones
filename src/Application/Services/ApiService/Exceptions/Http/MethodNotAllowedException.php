<?php

namespace Bayfront\Bones\Application\Services\ApiService\Exceptions\Http;

use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiException;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

/**
 * HTTP status 405.
 */
class MethodNotAllowedException extends ApiException implements ApiExceptionInterface
{

    /**
     * @inheritDoc
     */
    public function getHttpStatusCode(): int
    {
        return 405;
    }

}