<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Managed\Enums\GelfLevels;
use Psr\Log\LoggerInterface;

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

    abstract protected function setLogger();

    protected function handlePOST()
    {
        $level = (!empty($this->resource))? strtoupper($this->resource) : 'INFO';
        $level = GelfLevels::toValue($level);

        $this->logger->log($level, 'test', Session::getPublicInfo());
    }
}