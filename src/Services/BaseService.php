<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Session;
use Psr\Log\LoggerInterface;
use Config;

abstract class BaseService extends BaseRestService
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * BaseService constructor.
     *
     * @param array $settings
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $config = array_get($settings, 'config');

        if (empty($config)) {
            throw new InternalServerErrorException('No service configuration found for log service.');
        }

        $this->setLogger($config);
    }

    /**
     * @param $config
     */
    abstract protected function setLogger($config);

    /**
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function handlePOST()
    {
        $level = null;
        if ($this->resource !== $this->resourcePath) {
            $level = strtoupper($this->resource);
        }
        if (empty($level)) {
            $level = strtoupper($this->request->getPayloadData('level', 'INFO'));
        }

        $message = str_replace($this->resource . '/', null, $this->resourcePath);
        if (empty($message)) {
            $message = $this->request->getPayloadData('message');
        }

        if (empty($message)) {
            throw new BadRequestException('No message provided for logging');
        }

        $context = array_merge(
            ['_event' => $this->getRequestInfo()],
            ['_platform' => $this->getPlatformInfo()]
        );

        $result = $this->logger->log($level, $message, $context);

        return ['success' => $result];
    }

    /**
     * @return array
     */
    protected function handlePUT()
    {
        return $this->handlePOST();
    }

    /**
     * @return array
     */
    protected function getRequestInfo()
    {
        return [
            'request'  => $this->request->toArray(),
            'resource' => $this->resourcePath
        ];
    }

    /**
     * @return array
     */
    protected function getPlatformInfo()
    {
        return [
            'config'  => Config::get('df'),
            'session' => Session::all(),
        ];
    }
}