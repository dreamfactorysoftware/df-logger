<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Logger\Components\GelfLogger;
use DreamFactory\Core\Logger\Components\HttpLogger;
use DreamFactory\Core\Logger\Components\TcpLogger;
use DreamFactory\Core\Logger\Components\UdpLogger;
use Arr;

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
        $protocol = Arr::get($config, 'protocol');

        if (static::GELF === $protocol) {
            $this->logger = new GelfLogger(
                Arr::get($config, 'host', GelfLogger::DEFAULT_HOST),
                Arr::get($config, 'port', GelfLogger::DEFAULT_PORT)
            );
        } elseif (static::UDP === $protocol) {
            $this->logger = new UdpLogger(
                Arr::get($config, 'host', UdpLogger::DEFAULT_HOST),
                Arr::get($config, 'port', UdpLogger::DEFAULT_PORT)
            );
        } elseif (static::TCP === $protocol) {
            $this->logger = new TcpLogger(
                Arr::get($config, 'host', TcpLogger::DEFAULT_HOST),
                Arr::get($config, 'port', TcpLogger::DEFAULT_PORT)
            );
        } elseif (static::HTTP === $protocol) {
            $this->logger = new HttpLogger(
                Arr::get($config, 'host', HttpLogger::DEFAULT_HOST),
                Arr::get($config, 'port', HttpLogger::DEFAULT_PORT)
            );
        } else {
            throw new InternalServerErrorException('Unknown Logstash network protocol: [' . $protocol . ']');
        }
    }
}