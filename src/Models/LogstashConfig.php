<?php
namespace DreamFactory\Core\Logger\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Components\ServiceEventMapper;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\ServiceEventMap;

class LogstashConfig extends BaseServiceConfigModel
{
    use ServiceEventMapper;

    /** @var string */
    protected $table = 'logstash_config';

    /** @var array */
    protected $fillable = ['service_id', 'host', 'port', 'protocol', 'context'];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
        'port'       => 'integer',
        'context'    => 'array'
    ];

    /** {@inheritdoc} */
    public static function getConfig($id, $protect = true)
    {
        $config = parent::getConfig($id, $protect);
        /** @var ServiceEventMap $appRoleMaps */
        $serviceEventMaps = ServiceEventMap::whereServiceId($id)->get();
        $sems = (empty($serviceEventMaps)) ? [] : $serviceEventMaps->toArray();

        foreach ($sems as $key => $sem) {
            $data = json_decode($sem['data'], true);
            if (!empty($data)) {
                $sems[$key]['level'] = strtoupper(array_get($data, 'level'));
                $sems[$key]['message'] = array_get($data, 'message');
            } else {
                $sems[$key]['level'] = null;
                $sems[$key]['message'] = null;
            }
            unset($sems[$key]['data']);
        }

        $config['service_event_map'] = $sems;

        return $config;
    }

    /** {@inheritdoc} */
    public static function setConfig($id, $config)
    {
        if (isset($config['service_event_map'])) {
            $maps = $config['service_event_map'];
            foreach ($maps as $key => $map) {
                $maps[$key]['data'] =
                    json_encode(['level' => array_get($map, 'level'), 'message' => array_get($map, 'message')]);
                unset($maps[$key]['level']);
                unset($maps[$key]['message']);
            }
            if (!is_array($maps)) {
                throw new BadRequestException('Service to Event map must be an array.');
            }
            ServiceEventMap::setConfig($id, $maps);
        }

        parent::setConfig($id, $config);
    }

    /** {@inheritdoc} */
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();
        $sem = ServiceEventMap::getConfigSchema();
        $sem['items'] = [
            $sem['items'][0],
            [
                'type'       => 'picklist',
                'name'       => 'level',
                'label'      => 'Log Level',
                'allow_null' => false,
                'default'    => 'INFO',
                'values'     => [
                    [
                        'label' => 'EMERGENCY',
                        'name'  => 'EMERGENCY'
                    ],
                    [
                        'label' => 'ALERT',
                        'name'  => 'ALERT'
                    ],
                    [
                        'label' => 'CRITICAL',
                        'name'  => 'CRITICAL'
                    ],
                    [
                        'label' => 'ERROR',
                        'name'  => 'ERROR'
                    ],
                    [
                        'label' => 'WARNING',
                        'name'  => 'WARNING'
                    ],
                    [
                        'label' => 'NOTICE',
                        'name'  => 'NOTICE'
                    ],
                    [
                        'label' => 'INFO',
                        'name'  => 'INFO'
                    ],
                    [
                        'label' => 'DEBUG',
                        'name'  => 'DEBUG'
                    ]
                ]
            ],
            [
                'type'  => 'text',
                'name'  => 'message',
                'label' => 'Message'
            ]
        ];

        $schema[] = $sem;

        return $schema;
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'host':
                $schema['label'] = 'Host';
                $schema['default'] = '127.0.0.1';
                $schema['description'] = 'IP Address/Hostname that Logstash is listening on.';
                break;
            case 'port':
                $schema['label'] = 'Port';
                $schema['description'] = 'Port number that Logstash is listening on for inputs.';
                break;
            case 'protocol':
                $schema['type'] = 'picklist';
                $schema['default'] = 'gelf';
                $schema['values'] = [
                    [
                        'label' => 'GELF (UDP)',
                        'name'  => 'gelf'
                    ],
                    [
                        'label' => 'HTTP',
                        'name'  => 'http'
                    ],
                    [
                        'label' => 'TCP',
                        'name'  => 'tcp'
                    ],
                    [
                        'label' => 'UDP',
                        'name'  => 'udp'
                    ]
                ];
                $schema['label'] = 'Protocol/Format';
                $schema['description'] = 'Network protocol/format that Logstash input is configured for.';
                break;
            case 'context':
                $schema['type'] = 'multi_picklist';
                $schema['columns'] = 3;
                $schema['label'] = 'Log Context';
                $schema['legend'] = 'Select data object(s) to capture';
                $schema['description'] =
                    "Contextual data to capture with every log entry performed using this service.";
                $schema['values'] = [
                    [
                        'label' => 'Request All',
                        'name'  => '_event.request',
                    ],
                    [
                        'label' => 'Request Content',
                        'name'  => '_event.request.content',
                    ],
                    [
                        'label' => 'Request Content-Type',
                        'name'  => '_event.request.content_type',
                    ],
                    [
                        'label' => 'Request Headers',
                        'name'  => '_event.request.headers',
                    ],
                    [
                        'label' => 'Request Parameters',
                        'name'  => '_event.request.parameters',
                    ],
                    [
                        'label' => 'Request Method',
                        'name'  => '_event.request.method',
                    ],
                    [
                        'label' => 'Request Payload',
                        'name'  => '_event.request.payload',
                    ],
                    [
                        'label' => 'API Resource',
                        'name'  => '_event.resource',
                    ],
                    [
                        'label' => 'Response All (for events only)',
                        'name'  => '_event.response',
                    ],
                    [
                        'label' => 'Response Status Code (for events only)',
                        'name'  => '_event.response.status_code',
                    ],
                    [
                        'label' => 'Response Content (for events only)',
                        'name'  => '_event.response.content',
                    ],
                    [
                        'label' => 'Response Content-Type (for events only)',
                        'name'  => '_event.response.content_type',
                    ],
                    [
                        'label' => 'Platform All',
                        'name'  => '_platform',
                    ],
                    [
                        'label' => 'Platform Config',
                        'name'  => '_platform.config',
                    ],
                    [
                        'label' => 'Platform Session',
                        'name'  => '_platform.session',
                    ],
                    [
                        'label' => 'Platform Session User',
                        'name'  => '_platform.session.user',
                    ],
                    [
                        'label' => 'Platform Session API Key',
                        'name'  => '_platform.session.api_key',
                    ]
                ];
        }
    }
}