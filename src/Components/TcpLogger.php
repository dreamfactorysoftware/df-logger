<?php
namespace DreamFactory\Core\Logger\Components;

use Psr\Log\LoggerInterface;

class TcpLogger extends NetworkLogger implements LoggerInterface
{
    /**
     * @const tcp protocol
     */
    const PROTOCOL = 'tcp';

    /** {@inheritdoc} */
    public function __construct($host = self::DEFAULT_HOST, $port = self::DEFAULT_PORT)
    {
        parent::__construct($host, $port, static::PROTOCOL);
    }
}