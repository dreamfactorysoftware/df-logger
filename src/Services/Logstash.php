<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Logger\Components\GelfLogger;
use DreamFactory\Core\Logger\Components\HttpLogger;
use DreamFactory\Core\Logger\Components\TcpLogger;
use DreamFactory\Core\Logger\Components\UdpLogger;

class Logstash extends BaseService
{
    /** GELF format/UDP protocol */
    const GELF = 'gelf';

    /** UDP protocol */
    const UDP = 'udp';

    /** TCP protocol */
    const TCP = 'tcp';

    /** HTTP protocol */
    const HTTP = 'http';

    /**
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function setLogger($config)
    {
        $protocol = array_get($config, 'protocol');

        if (static::GELF === $protocol) {
            $this->logger = new GelfLogger(
                array_get($config, 'host', GelfLogger::DEFAULT_HOST),
                array_get($config, 'port', GelfLogger::DEFAULT_PORT)
            );
        } elseif (static::UDP === $protocol) {
            $this->logger = new UdpLogger(
                array_get($config, 'host', GelfLogger::DEFAULT_HOST),
                array_get($config, 'port', GelfLogger::DEFAULT_PORT)
            );
        } elseif (static::TCP === $protocol) {
            $this->logger = new TcpLogger(
                array_get($config, 'host', GelfLogger::DEFAULT_HOST),
                array_get($config, 'port', GelfLogger::DEFAULT_PORT)
            );
        } elseif (static::HTTP === $protocol) {
            $this->logger = new HttpLogger(
                array_get($config, 'host', GelfLogger::DEFAULT_HOST),
                array_get($config, 'port', GelfLogger::DEFAULT_PORT)
            );
        } else {
            throw new InternalServerErrorException('Unknown Logstash network protocol: [' . $protocol . ']');
        }
    }
}