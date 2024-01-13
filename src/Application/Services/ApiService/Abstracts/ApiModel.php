<?php /** @noinspection PhpUnused */

namespace Bayfront\Bones\Application\Services\ApiService\Abstracts;

use Bayfront\Bones\Abstracts\Model;
use Bayfront\Bones\Application\Services\ApiService\ApiService;
use Bayfront\Bones\Application\Services\ApiService\Interfaces\ApiModelInterface;

abstract class ApiModel extends Model implements ApiModelInterface
{

    protected ApiService $apiService;

    public function __construct(ApiService $apiService)
    {

        $this->apiService = $apiService;

        $this->apiService->events->doEvent('api.model', $this);

        parent::__construct($this->apiService->events);

    }

    public const ACTION_CREATE = 'create';
    public const ACTION_LIST = 'list';
    public const ACTION_READ = 'read';
    public const ACTION_UPDATE = 'update';
    public const ACTION_REPLACE = 'replace';
    public const ACTION_DELETE = 'delete';

    /**
     * Handle the results of an action-related method and return its original value.
     *
     * @param mixed $result
     * @param string $action
     * @return mixed
     */
    public function tookAction(mixed $result, string $action): mixed
    {
        $this->apiService->events->doEvent('api.model.action', $result, $this->getModelIdentifier(), $action);
        return $result;
    }

}