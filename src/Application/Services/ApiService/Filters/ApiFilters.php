<?php

namespace Bayfront\Bones\Application\Services\ApiService\Filters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\FilterSubscriber;
use Bayfront\Bones\Application\Services\ApiService\ApiService;
use Bayfront\Bones\Application\Services\Filters\FilterSubscription;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Interfaces\FilterSubscriberInterface;

class ApiFilters extends FilterSubscriber implements FilterSubscriberInterface
{

    protected ApiService $apiService;

    /**
     * The container will resolve any dependencies.
     */

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * @inheritDoc
     */

    public function getSubscriptions(): array
    {
        return [
            new FilterSubscription('api.response.raw', [$this, 'addResponseMeta'], 10)
        ];
    }

    /**
     * Add metadata to response array.
     *
     * At the api.response or api.response.raw event,
     * add this meta info filter with a very low priority
     * to ensure the elapsed time is as accurate as possible.
     *
     * @param array $response
     * @return array
     */

    public function addResponseMeta(array $response): array
    {

        $meta = Arr::get($response, 'meta', []);

        $meta = array_merge($meta, [
            'apiVersion' => $this->apiService->spec->getInfo('version'),
            'elapsedSecs' => App::getElapsedTime()
        ]);

        $response['meta'] = $meta;

        return $response;

    }

}