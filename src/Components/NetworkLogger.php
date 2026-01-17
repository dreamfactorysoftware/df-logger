<?php
namespace DreamFactory\Core\Logger\Components;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ServiceUnavailableException;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
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
     * @const float Default connection timeout in seconds
     */
    const DEFAULT_TIMEOUT = 2.0;
    /**
     * @const string Default failure behavior: 'ignore', 'fail_request', 'fallback_file'
     */
    const DEFAULT_ON_FAILURE = 'ignore';
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
     * @var float Connection timeout in seconds
     */
    protected $timeout;
    /**
     * @var string Behavior on failure: 'ignore', 'fail_request', 'fallback_file'
     */
    protected $onFailure;

    /**
     * NetworkLogger constructor.
     *
     * @param        $host
     * @param        $port
     * @param string $protocol
     * @param float  $timeout Connection timeout in seconds
     * @param string $onFailure Behavior on failure: 'ignore', 'fail_request', 'fallback_file'
     */
    public function __construct($host, $port, $protocol = 'udp', $timeout = null, $onFailure = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->protocol = $protocol;
        $this->timeout = $timeout ?? static::DEFAULT_TIMEOUT;
        $this->onFailure = $onFailure ?? static::DEFAULT_ON_FAILURE;
    }

    /** {@inheritdoc} */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        try {
            $levelVal = Logger::toMonologLevel($level);
            // Monolog 3.x returns Level enum, Monolog 2.x returns int
            if ($levelVal instanceof Level) {
                $levelVal = $levelVal->value;
            } elseif (!is_int($levelVal)) {
                throw new BadRequestException('Unknown log level [' . $level . ']');
            }
            $context['_message'] = $message;
            $context['_level'] = $levelVal;

            $this->send(json_encode($context, JSON_UNESCAPED_SLASHES));
        } catch (ServiceUnavailableException $exception) {
            // Re-throw to block request when on_failure='fail_request'
            throw $exception;
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
     * @throws ServiceUnavailableException
     */
    public function send(string|\Stringable $message)
    {
        $startTime = microtime(true);
        $_url = $this->protocol . '://' . $this->host . ':' . $this->port;
        $_sock = null;

        try {
            Log::debug("Logstash: Connecting to {$_url} (timeout: {$this->timeout}s)");

            $_sock = @stream_socket_client(
                $_url,
                $errno,
                $errstr,
                $this->timeout
            );

            if (!$_sock) {
                $elapsed = round(microtime(true) - $startTime, 3);
                Log::warning("Logstash: Connection failed to {$_url} after {$elapsed}s - [$errno] $errstr");
                return $this->handleFailure("Connection failed: [$errno] $errstr", $message);
            }

            // Set write timeout to match connection timeout
            stream_set_timeout($_sock, (int)$this->timeout, (int)(($this->timeout - (int)$this->timeout) * 1000000));

            if (!fwrite($_sock, $message)) {
                $elapsed = round(microtime(true) - $startTime, 3);
                Log::warning("Logstash: Write failed to {$_url} after {$elapsed}s");
                return $this->handleFailure("Write failed", $message);
            }

            $elapsed = round(microtime(true) - $startTime, 3);
            Log::debug("Logstash: Successfully sent to {$_url} in {$elapsed}s");

            return true;

        } catch (ServiceUnavailableException $ex) {
            // Re-throw to block request when on_failure='fail_request'
            throw $ex;
        } catch (\Exception $ex) {
            $elapsed = round(microtime(true) - $startTime, 3);
            Log::error("Logstash: Exception after {$elapsed}s - " . $ex->getMessage());
            Log::error($ex->getTraceAsString());
            return $this->handleFailure($ex->getMessage(), $message);
        } finally {
            if (is_resource($_sock)) {
                fclose($_sock);
            }
        }
    }

    /**
     * Handle send failure based on configured behavior
     *
     * @param string $reason Failure reason
     * @param string|\Stringable $message The message that failed to send
     * @return bool
     * @throws ServiceUnavailableException
     */
    protected function handleFailure($reason, $message)
    {
        switch ($this->onFailure) {
            case 'fail_request':
                throw new ServiceUnavailableException(
                    "Logging service unavailable: $reason. Request blocked per logging policy."
                );

            case 'fallback_file':
                Log::warning("Logstash: Falling back to file logging - $reason");
                Log::info("Logstash fallback message: " . (is_string($message) ? $message : json_encode($message)));
                return true; // Don't block request, message is preserved in file log

            case 'ignore':
            default:
                Log::debug("Logstash: Ignoring failure - $reason");
                return false; // Don't block request, message is lost
        }
    }
}