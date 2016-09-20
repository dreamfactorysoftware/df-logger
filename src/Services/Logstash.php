<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Managed\Support\GelfLogger;

class Logstash extends BaseService
{
    protected function setLogger($config)
    {
        $this->logger = new GelfLogger();
        $this->logger->setHost(array_get($config, 'host'));
        $this->logger->setPort(array_get($config, 'port'));
    }
}