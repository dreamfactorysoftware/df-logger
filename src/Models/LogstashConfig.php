<?php
namespace DreamFactory\Core\Models;

class LogstashConfig extends BaseServiceConfigModel
{
    protected $table = 'logstash_config';

    protected $fillable = ['service_id', 'host', 'port', 'protocol', 'options'];

    protected $casts = [
        'service_id'     => 'integer',
        'options'        => 'array'
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
                $schema['description'] = 'IP Address/Hostname of your Logstash server.';
                break;
            case 'port':
                $schema['label'] = 'Port';
                $schema['description'] = 'Port number you Logstash server is listening on for inputs.';
                break;
            case 'options':
                $schema['type'] = 'object';
                $schema['object'] =
                    [
                        'key'   => ['label' => 'Name', 'type' => 'string'],
                        'value' => ['label' => 'Value', 'type' => 'string']
                    ];
                $schema['description'] =
                    'An array of options for your Logstash service.';
        }
    }
}