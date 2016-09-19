<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Managed\Support\GelfLogger;

class Logstash extends BaseService
{
    protected function setLogger()
    {
        $this->logger = new GelfLogger();
    }
}