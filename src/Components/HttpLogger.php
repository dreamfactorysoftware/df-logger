<?php
namespace DreamFactory\Core\Logger\Components;

use DreamFactory\Core\Utility\Curl;
use Illuminate\Support\Facades\Log;
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
    public function __construct($host = self::DEFAULT_HOST, $port = self::DEFAULT_PORT, $timeout = null, $onFailure = null)
    {
        parent::__construct($host, $port, static::PROTOCOL, $timeout, $onFailure);
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function send($message)
    {
        $startTime = microtime(true);
        $url = $this->protocol . '://' . $this->host . ':' . $this->port;

        try {
            Log::debug("Logstash HTTP: Posting to {$url} (timeout: {$this->timeout}s)");

            $result = Curl::post($url, $message, [], $this->timeout);
            $elapsed = round(microtime(true) - $startTime, 3);

            if ('ok' === $result) {
                Log::debug("Logstash HTTP: Successfully sent to {$url} in {$elapsed}s");
                return true;
            }

            Log::warning("Logstash HTTP: Unexpected response from {$url} after {$elapsed}s: " . print_r($result, true));
            return $this->handleFailure("Unexpected response: " . print_r($result, true), $message);

        } catch (\Exception $ex) {
            $elapsed = round(microtime(true) - $startTime, 3);
            Log::error("Logstash HTTP: Exception after {$elapsed}s - " . $ex->getMessage());
            return $this->handleFailure($ex->getMessage(), $message);
        }
    }
}