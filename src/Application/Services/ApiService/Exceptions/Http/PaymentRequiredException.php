<?php

namespace Bayfront\Bones\Application\Services\ApiService\Exceptions\Http;

use Bayfront\Bones\Application\Services\ApiService\Exceptions\ApiException;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiExceptionInterface;

/**
 * HTTP status 402.
 */
class PaymentRequiredException extends ApiException implements ApiExceptionInterface
{

    /**
     * @inheritDoc
     */
    public function getHttpStatusCode(): int
    {
        return 402;
    }

}