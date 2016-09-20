<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Session;
use Psr\Log\LoggerInterface;
use Config;

abstract class BaseService extends BaseRestService
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $config = array_get($settings, 'config');

        if (empty($config)) {
            throw new InternalServerErrorException('No service configuration found for log service.');
        }

        $this->ttl = array_get($config, 'default_ttl', \Config::get('df.default_cache_ttl', 300));
        $this->setLogger($config);
    }

    abstract protected function setLogger($config);

    abstract protected function getLogLevel($key);

    protected function handlePOST()
    {
        $level = 'INFO';
        if($this->resource !== $this->resourcePath){
            $level = strtoupper($this->resource);
        }
        $level = $this->getLogLevel($level);
        $message = str_replace($this->resource . '/', null, $this->resourcePath);
        $context = array_merge(
            ['_event' => $this->getRequestInfo()],
            ['_platform' => $this->getPlatformInfo()]
        );

        $result = $this->logger->log($level, $message, $context);

        return ['success' => $result];
    }

    protected function handlePUT()
    {
        return $this->handlePOST();
    }

    protected function getRequestInfo()
    {
        return [
            'request' => $this->request->toArray(),
            'resource' => $this->resourcePath
        ];
    }

    protected function getPlatformInfo()
    {
        return [
            'config'  => Config::get('df'),
            'session' => Session::all(),
        ];
    }
}