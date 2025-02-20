<?php
namespace DreamFactory\Core\Logger\Components;

use DreamFactory\Core\Exceptions\BadRequestException;
use Illuminate\Support\Facades\Log;
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
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        try {
            $levelVal = Logger::toMonologLevel($level);
            if(!is_int($levelVal)){
                throw new BadRequestException('Unknown log level [' . $level . ']');
            }
            $context['_message'] = $message;
            $context['_level'] = $levelVal;

            $this->send(json_encode($context, JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
        }

    }

    /** {@inheritdoc} */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /** {@inheritdoc} */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    /** {@inheritdoc} */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /** {@inheritdoc} */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /** {@inheritdoc} */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /** {@inheritdoc} */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /** {@inheritdoc} */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /** {@inheritdoc} */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * @param string|\Stringable $message
     *
     * @return bool
     */
    public function send(string|\Stringable $message)
    {
        try {
            $_url = $this->protocol . '://' . $this->host . ':' . $this->port;
            $_sock = stream_socket_client($_url);

            if (!fwrite($_sock, $message)) {
                return false;
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
            //  Failure is not an option
            return false;
        }

        return true;
    }
}