<?php
namespace DreamFactory\Core\Logger\Components;

use Psr\Log\LoggerInterface;

class UdpLogger extends NetworkLogger implements LoggerInterface
{
    /**
     * @const udp protocol
     */
    const PROTOCOL = 'udp';

    /** {@inheritdoc} */
    public function __construct($host = self::DEFAULT_HOST, $port = self::DEFAULT_PORT)
    {
        parent::__construct($host, $port, static::PROTOCOL);
    }
}