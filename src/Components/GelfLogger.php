<?php
namespace DreamFactory\Core\Logger\Components;

use Psr\Log\LoggerInterface;
use DreamFactory\Core\Exceptions\BadRequestException;

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

    /** {@inheritdoc} */
    public function log($level, $message, array $context = [])
    {
        try {
            $levelVal = GelfLevels::toValue($level);
        } catch (\InvalidArgumentException $e){
            throw new BadRequestException('Unknown log level [' . $level . ']');
        }
        $_message = new GelfMessage($context);
        $_message->setLevel($levelVal)->setFullMessage($message);

        return $this->send($_message);
    }

    /** {@inheritdoc} */
    public function emergency($message, array $context = [])
    {
        $this->log(GelfLevels::EMERGENCY, $message, $context);
    }

    /** {@inheritdoc} */
    public function alert($message, array $context = [])
    {
        $this->log(GelfLevels::ALERT, $message, $context);
    }

    /** {@inheritdoc} */
    public function critical($message, array $context = [])
    {
        $this->log(GelfLevels::CRITICAL, $message, $context);
    }

    /** {@inheritdoc} */
    public function error($message, array $context = [])
    {
        $this->log(GelfLevels::ERROR, $message, $context);
    }

    /** {@inheritdoc} */
    public function warning($message, array $context = [])
    {
        $this->log(GelfLevels::WARNING, $message, $context);
    }

    /** {@inheritdoc} */
    public function notice($message, array $context = [])
    {
        $this->log(GelfLevels::NOTICE, $message, $context);
    }

    /** {@inheritdoc} */
    public function info($message, array $context = [])
    {
        $this->log(GelfLevels::INFO, $message, $context);
    }

    /** {@inheritdoc} */
    public function debug($message, array $context = [])
    {
        $this->log(GelfLevels::DEBUG, $message, $context);
    }

    /** {@inheritdoc} */
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
            //  Failure is not an option
            return false;
        }

        return true;
    }

    /**
     * Static method for preparing a GELF message to be sent
     *
     * @param GelfMessage $message
     *
     * @return mixed
     */
    protected function prepareMessage(GelfMessage $message)
    {
        try {
            if (false === ($_gzJson = gzcompress($message->toJson()))) {
                return false;
            }

            //  If we are less than the max chunk size, we're done
            if (strlen($_gzJson) <= static::MAX_CHUNK_SIZE) {
                return [$_gzJson];
            }
        } catch (\Exception $_ex) {
            //  Eschew failure
            return false;
        }

        return $this->prepareChunks(str_split($_gzJson, static::MAX_CHUNK_SIZE));
    }

    /**
     * Static method for packing a chunk of GELF data
     *
     * @param array  $chunks The array of chunks of gzipped JSON GELF data to prepare
     * @param string $msgId  The 8-byte message id, same for entire chunk set
     *
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
            //  Failure is not an option
            return false;
        }
    }
}