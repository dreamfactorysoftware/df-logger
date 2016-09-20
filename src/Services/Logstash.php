<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Logger\Components\GelfLogger;
use DreamFactory\Core\Logger\Components\GelfLevels;

class Logstash extends BaseService
{
    protected function setLogger($config)
    {
        $this->logger = new GelfLogger(
            array_get($config, 'host', GelfLogger::DEFAULT_HOST),
            array_get($config, 'port', GelfLogger::DEFAULT_PORT)
        );
    }

    protected function getLogLevel($key)
    {
        return GelfLevels::toValue($key);
    }
}