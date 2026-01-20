<?php
namespace DreamFactory\Core\Logger\Components;

use Psr\Log\LoggerInterface;
use DreamFactory\Core\Exceptions\BadRequestException;
use Stringable;

class GelfLogger extends UdpLogger implements LoggerInterface
{
    /**
     * @const integer Maximum message size before splitting into chunks
     */
    const MAX_CHUNK_SIZE = 8154;
    /**
     * @const integer Maximum number of chunks allowed by GELF
     */
    const MAX_CHUNKS_ALLOWED = 128;

    /**
     * Logs an arbitrary level message.
     *
     * @param mixed $level
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        try {
            $levelVal = GelfLevels::toValue($level);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestException('Unknown log level [' . $level . ']');
        }
        $_message = new GelfMessage($context);
        $_message->setLevel($levelVal)->setFullMessage($message);

        $this->send($_message);
    }

    /**
     * System is unusable.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::CRITICAL, $message, $context);
    }

    /**
     * Error conditions.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::ERROR, $message, $context);
    }

    /**
     * Warning conditions.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::WARNING, $message, $context);
    }

    /**
     * Normal but significant conditions.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::NOTICE, $message, $context);
    }

    /**
     * Informational messages.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::INFO, $message, $context);
    }

    /**
     * Debug-level messages.
     *
     * @param Stringable|string $message
     * @param array $context
     * @return void
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(GelfLevels::DEBUG, $message, $context);
    }

    /**
     * Sends a Gelf message to the server.
     *
     * @param GelfMessage $message
     * @return bool
     */
    public function send($message)
    {
        if (!($message instanceof GelfMessage)) {
            throw new \InvalidArgumentException('Message is not a GelfMessage.');
        }

        try {
            if (false === ($_chunks = $this->prepareMessage($message))) {
                return false;
            }

            $_url = $this->protocol . '://' . $this->host . ':' . $this->port;
            $_sock = stream_socket_client($_url);

            foreach ($_chunks as $_chunk) {
                if (!fwrite($_sock, $_chunk)) {
                    return false;
                }
            }
        } catch (\Exception $_ex) {
            // Failure is not an option
            return false;
        }

        return true;
    }

    /**
     * Prepares a GELF message to be sent, handling large message sizes.
     *
     * @param GelfMessage $message
     * @return mixed
     */
    protected function prepareMessage(GelfMessage $message)
    {
        try {
            if (false === ($_gzJson = gzcompress($message->toJson()))) {
                return false;
            }

            if (strlen($_gzJson) <= static::MAX_CHUNK_SIZE) {
                return [$_gzJson];
            }
        } catch (\Exception $_ex) {
            return false;
        }

        return $this->prepareChunks(str_split($_gzJson, static::MAX_CHUNK_SIZE));
    }

    /**
     * Splits a large GELF message into smaller chunks for transmission.
     *
     * @param array $chunks
     * @param string $msgId
     * @return mixed An array of packed chunks ready to send
     */
    protected function prepareChunks($chunks, $msgId = null)
    {
        try {
            $msgId = $msgId ?: hash('sha256', microtime(true) . rand(10000, 99999), true);
            $_sequence = 0;
            $_count = count($chunks);

            if ($_count > static::MAX_CHUNKS_ALLOWED) {
                return false;
            }

            $_prepared = [];

            foreach ($chunks as $_chunk) {
                $_prepared[] = pack('CC', 30, 15) . $msgId . pack('nn', $_sequence++, $_count) . $_chunk;
            }

            return $_prepared;
        } catch (\Exception $_ex) {
            return false;
        }
    }
}
