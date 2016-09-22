<?php
namespace DreamFactory\Core\Logger\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class LogstashConfig extends BaseServiceConfigModel
{
    /** @var string */
    protected $table = 'logstash_config';

    /** @var array */
    protected $fillable = ['service_id', 'host', 'port', 'protocol'];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer'
    ];

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
        }
    }
}