<?php

namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Contracts\ServiceRequestInterface;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;
use Psr\Log\LoggerInterface;
use Config;
use Arr;
use Str;

abstract class BaseService extends BaseRestService
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var array  Log context */
    protected $context = [];

    protected $contextKeys = null;

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

        if (empty($this->config)) {
            throw new InternalServerErrorException('No service configuration found for log service.');
        }

        $this->setLogger($this->config);
        // Too early (request object is not set yet) to set context.
        // Therefore, store the contextKeys from config for now.
        $this->contextKeys = Arr::get($this->config, 'context');;
    }

    /**
     * @param $config
     */
    abstract protected function setLogger($config);

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param array $context
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function handlePOST(array $context = [])
    {
        $level = null;
        if ($this->resource !== $this->resourcePath) {
            $level = strtoupper($this->resource);
        }
        if (empty($level)) {
            $level = strtoupper($this->request->getPayloadData('level', 'INFO'));
        }

        $message = str_replace($this->resource . '/', '', $this->resourcePath);
        if (empty($message)) {
            $message = $this->request->getPayloadData('message');
        }

        if (empty($message)) {
            throw new BadRequestException('No message provided for logging');
        }

        $this->setContextByKeys();
        $result = $this->log($level, $message, $context);

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
     * Writes log.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, $context = [])
    {
        $context = array_merge($this->context, $context);
        $result = $this->logger->log($level, $message, $context);

        return $result;
    }

    /**
     * Returns log context data.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns default context data.
     *
     * @return array
     */
    protected function getDefaultContext()
    {
        $context = [
            '_event'    => $this->getRequestInfo(),
            '_platform' => $this->getPlatformInfo()
        ];

        return $context;
    }

    /**
     * Sets log context data.
     *
     * @param array $context
     */
    public function setContext(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * Let context data by event keys (example: _event.request, _event.response, _platform.session etc.)
     *
     * @param null $keys
     * @param null $allContext
     */
    public function setContextByKeys($keys = null, $allContext = null)
    {
        if (empty($keys)) {
            $keys = $this->contextKeys;
        }
        $context = [];
        if (!empty($keys)) {
            if (is_string($keys)) {
                $keys = explode(',', $keys);
            }
            if (empty($allContext)) {
                $allContext = $this->getDefaultContext();
            }
            foreach ($keys as $key) {
                Arr::set($context, $key, Arr::get($allContext, $key));
            }
        }

        $this->context = $context;
    }

    /**
     * @return array
     */
    protected function getRequestInfo()
    {
        if ($this->request instanceof ServiceRequestInterface) {
            $request = $this->request->toArray();

            return [
                'request'  => $request,
                'resource' => $this->resourcePath
            ];
        } else {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getPlatformInfo()
    {
        $platform = [
            'config'  => Config::get('df'),
            'session' => Session::all(),
        ];

        unset($platform['session']['lookup']);
        unset($platform['session']['lookup_secret']);

        return $platform;
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $capitalized = Str::camel($this->name);

        $base = [
            '/'                  => [
                'post' => [
                    'summary'     => 'Create one log entry',
                    'description' => 'Creates one log entry.',
                    'operationId' => 'create' . $capitalized . 'Entry',
                    'requestBody' => [
                        'description' => 'Content - Log level and message.',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'level'   => [
                                            'type'        => 'string',
                                            'description' => 'Valid levels: emergency, alert, critical, error, warning, notice, info, debug'
                                        ],
                                        'message' => [
                                            'type'        => 'string',
                                            'description' => 'Your log message goes here'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses'   => [
                        '201' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
            ],
            '/{message}'         => [
                'parameters' => [
                    [
                        'name'        => 'message',
                        'description' => 'URL encoded log message.',
                        'type'        => 'string',
                        'in'          => 'path',
                        'required'    => true,
                    ],
                ],
                'post'       => [
                    'summary'     => 'Create one log entry',
                    'description' => 'Creates one log entry.',
                    'operationId' => 'create' . $capitalized . 'EntryMessage',
                    'responses'   => [
                        '201' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
            ],
            '/{level}/{message}' => [
                'parameters' => [
                    [
                        'name'        => 'level',
                        'description' => 'Valid levels: emergency, alert, critical, error, warning, notice, info, debug',
                        'type'        => 'string',
                        'in'          => 'path',
                        'required'    => true,
                    ],
                    [
                        'name'        => 'message',
                        'description' => 'URL encoded log message.',
                        'type'        => 'string',
                        'in'          => 'path',
                        'required'    => true,
                    ]
                ],
                'post'       => [
                    'summary'     => 'Create one log entry for a specific log level',
                    'description' => 'Creates one log entry.',
                    'operationId' => 'create' . $capitalized . 'EntryByLevel',
                    'responses'   => [
                        '201' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
            ],
        ];

        return $base;
    }
}