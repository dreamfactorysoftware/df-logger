<?php
namespace DreamFactory\Core\Logger\Components;

class NetworkLogger
{
    protected $host;

    protected $port;

    protected $protocol;

    public function __construct($host, $port, $protocol = 'udp')
    {
        $this->host = $host;
        $this->port = $port;
        $this->protocol = $protocol;
    }

    public function emergency($message, array $context = array())
    {
        // TODO: Implement emergency() method.
    }

    public function alert($message, array $context = array())
    {
        // TODO: Implement alert() method.
    }

    public function critical($message, array $context = array())
    {
        // TODO: Implement critical() method.
    }

    public function error($message, array $context = array())
    {
        // TODO: Implement error() method.
    }

    public function warning($message, array $context = array())
    {
        // TODO: Implement warning() method.
    }

    public function notice($message, array $context = array())
    {
        // TODO: Implement notice() method.
    }

    public function debug($message, array $context = array())
    {
        // TODO: Implement debug() method.
    }

    public function info($message, array $context = array())
    {
        // TODO: Implement info() method.
    }

    public function log($level, $message, array $context = array())
    {
        // TODO: Implement log() method.
    }

    /**
     * @param $message mixed
     */
    public function send($message)
    {

    }
}