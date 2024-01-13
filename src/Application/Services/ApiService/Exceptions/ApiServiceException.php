<?php

namespace Bayfront\Bones\Application\Services\ApiService\Exceptions;

use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

/**
 * Errors thrown by the API service.
 */
class ApiServiceException extends ApiException implements ApiExceptionInterface
{

    /**
     * @inheritDoc
     */
    public function getHttpStatusCode(): int
    {
        return 500;
    }

}