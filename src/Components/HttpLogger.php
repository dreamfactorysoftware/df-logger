<?php
namespace DreamFactory\Core\Logger\Components;

use DreamFactory\Library\Utility\Curl;
use Psr\Log\LoggerInterface;

class HttpLogger extends NetworkLogger implements LoggerInterface
{
    /**
     * @const integer Port that logstash listens on
     */
    const DEFAULT_PORT = 8080;
    /**
     * @const http protocol
     */
    const PROTOCOL = 'http';

    /** {@inheritdoc} */
    public function __construct($host = self::DEFAULT_HOST, $port = self::DEFAULT_PORT)
    {
        parent::__construct($host, $port, static::PROTOCOL);
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function send($message)
    {
        $url = $this->protocol . '://' . $this->host . ':' . $this->port;
        $result = Curl::post($url, $message);

        return ('ok' === $result) ? true : false;
    }
}