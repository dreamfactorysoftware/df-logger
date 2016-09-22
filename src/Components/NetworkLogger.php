<?php
namespace DreamFactory\Core\Logger\Components;

use Monolog\Logger;

abstract class NetworkLogger
{
    /**
     * @var string Logstash target of TCP messages
     */
    const DEFAULT_HOST = 'localhost';
    /**
     * @const integer Port that logstash listens on
     */
    const DEFAULT_PORT = 12202;
    /**
     * @var string Logstash host
     */
    protected $host;
    /**
     * @var integer Logstash port
     */
    protected $port;
    /**
     * @var string Communication protocol
     */
    protected $protocol;

    /**
     * NetworkLogger constructor.
     *
     * @param        $host
     * @param        $port
     * @param string $protocol
     */
    public function __construct($host, $port, $protocol = 'udp')
    {
        $this->host = $host;
        $this->port = $port;
        $this->protocol = $protocol;
    }

    /** {@inheritdoc} */
    public function log($level, $message, array $context = [])
    {
        $context['_message'] = $message;
        $context['_level'] = Logger::toMonologLevel($level);

        return $this->send(json_encode($context, JSON_UNESCAPED_SLASHES));
    }

    /** {@inheritdoc} */
    public function emergency($message, array $context = [])
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /** {@inheritdoc} */
    public function alert($message, array $context = [])
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    /** {@inheritdoc} */
    public function critical($message, array $context = [])
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /** {@inheritdoc} */
    public function error($message, array $context = [])
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /** {@inheritdoc} */
    public function warning($message, array $context = [])
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /** {@inheritdoc} */
    public function notice($message, array $context = [])
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /** {@inheritdoc} */
    public function info($message, array $context = [])
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /** {@inheritdoc} */
    public function debug($message, array $context = [])
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function send($message)
    {
        try {
            $_url = $this->protocol . '://' . $this->host . ':' . $this->port;
            $_sock = stream_socket_client($_url);

            if (!fwrite($_sock, $message)) {
                return false;
            }
        } catch (\Exception $_ex) {
            //  Failure is not an option
            return false;
        }

        return true;
    }
}