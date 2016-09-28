<?php
namespace DreamFactory\Core\Logger\Services;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Library\Utility\Inflector;
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
        Session::replaceLookups($config, true);

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

    /** {@inheritdoc} */
    protected function handleGET()
    {
        return false;
    }

    /** {@inheritdoc} */
    protected function handlePATCH()
    {
        return false;
    }

    /** {@inheritdoc} */
    protected function handleDELETE()
    {
        return false;
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

    /** {@inheritdoc} */
    public static function getApiDocInfo($service)
    {
        $base = parent::getApiDocInfo($service);
        $name = strtolower($service->name);
        $capitalized = Inflector::camelize($service->name);

        $base['paths'] = [
            '/' . $name                                   => [
                'post' => [
                    'tags'              => [$name],
                    'summary'           => 'create' .
                        $capitalized .
                        'Entries() - Create one log entry',
                    'operationId'       => 'create' . $capitalized . 'Entries',
                    'x-publishedEvents' => [
                        $name . '.create'
                    ],
                    'consumes'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'produces'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'parameters'        => [
                        [
                            'name'        => 'body',
                            'description' => 'Content - Log level and message.',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'level'   => [
                                        'type'        => 'string',
                                        'description' => 'Valid levels: emergency, alert, critical, error, warning, notice, info, debug'
                                    ],
                                    'message' => [
                                        'type'        => 'string',
                                        'description' => 'Your log message goes here'
                                    ]
                                ]
                            ],
                            'in'          => 'body',
                        ]
                    ],
                    'responses'         => [
                        '201'     => [
                            'description' => 'Success',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean'
                                    ]
                                ]
                            ]
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Creates one log entry.'
                ],
                'put'  => [
                    'tags'              => [$name],
                    'summary'           => 'create' .
                        $capitalized .
                        'Entries() - Create one log entry',
                    'operationId'       => 'create' . $capitalized . 'Entries',
                    'x-publishedEvents' => [
                        $name . '.create'
                    ],
                    'consumes'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'produces'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'parameters'        => [
                        [
                            'name'        => 'body',
                            'description' => 'Content - Log level and message.',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'level'   => [
                                        'type'        => 'string',
                                        'description' => 'Valid levels: emergency, alert, critical, error, warning, notice, info, debug'
                                    ],
                                    'message' => [
                                        'type'        => 'string',
                                        'description' => 'Your log message goes here'
                                    ]
                                ]
                            ],
                            'in'          => 'body',
                        ]
                    ],
                    'responses'         => [
                        '201'     => [
                            'description' => 'Success',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean'
                                    ]
                                ]
                            ]
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Creates one log entry.'
                ]
            ],
            '/' . $name . '/{urlencoded_message}'         => [
                'parameters' => [
                    [
                        'name'        => 'urlencoded_message',
                        'description' => 'URL encoded log message.',
                        'type'        => 'string',
                        'in'          => 'path',
                        'required'    => true,
                    ],
                ],
                'post'       => [
                    'tags'              => [$name],
                    'summary'           => 'create' .
                        $capitalized .
                        'Entries() - Create one log entry',
                    'operationId'       => 'create' . $capitalized . 'Entries',
                    'x-publishedEvents' => [
                        $name . '.create'
                    ],
                    'consumes'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'produces'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'responses'         => [
                        '201'     => [
                            'description' => 'Success',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean'
                                    ]
                                ]
                            ]
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Creates one log entry.'
                ],
                'put'        => [
                    'tags'              => [$name],
                    'summary'           => 'create' .
                        $capitalized .
                        'Entries() - Create one log entry',
                    'operationId'       => 'create' . $capitalized . 'Entries',
                    'x-publishedEvents' => [
                        $name . '.create'
                    ],
                    'consumes'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'produces'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'responses'         => [
                        '201'     => [
                            'description' => 'Success',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean'
                                    ]
                                ]
                            ]
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Creates one log entry.'
                ]
            ],
            '/' . $name . '/{level}/{urlencoded_message}' => [
                'parameters' => [
                    [
                        'name'        => 'level',
                        'description' => 'Valid levels: emergency, alert, critical, error, warning, notice, info, debug',
                        'type'        => 'string',
                        'in'          => 'path',
                        'required'    => true,
                    ],
                    [
                        'name'        => 'urlencoded_message',
                        'description' => 'URL encoded log message.',
                        'type'        => 'string',
                        'in'          => 'path',
                        'required'    => true,
                    ]
                ],
                'post'       => [
                    'tags'              => [$name],
                    'summary'           => 'create' .
                        $capitalized .
                        'Entries() - Create one log entry',
                    'operationId'       => 'create' . $capitalized . 'Entries',
                    'x-publishedEvents' => [
                        $name . '.create'
                    ],
                    'consumes'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'produces'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'responses'         => [
                        '201'     => [
                            'description' => 'Success',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean'
                                    ]
                                ]
                            ]
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Creates one log entry.'
                ],
                'put'        => [
                    'tags'              => [$name],
                    'summary'           => 'create' .
                        $capitalized .
                        'Entries() - Create one log entry',
                    'operationId'       => 'create' . $capitalized . 'Entries',
                    'x-publishedEvents' => [
                        $name . '.create'
                    ],
                    'consumes'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'produces'          => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
                    'responses'         => [
                        '201'     => [
                            'description' => 'Success',
                            'schema'      => [
                                'type'       => 'object',
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean'
                                    ]
                                ]
                            ]
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Creates one log entry.'
                ]
            ],
        ];

        return $base;
    }
}