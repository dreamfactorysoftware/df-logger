<?php
namespace DreamFactory\Core\Logger;

use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Logger\Handlers\Events\LoggingEventHandler;
use DreamFactory\Core\Logger\Services\Logstash;
use DreamFactory\Core\Logger\Models\LogstashConfig;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'logstash',
                    'label'           => 'Logstash',
                    'description'     => 'Logstash service.',
                    'group'           => ServiceTypeGroups::LOG,
                    'config_handler'  => LogstashConfig::class,
                    'default_api_doc' => function ($service){
                        return $this->buildServiceDoc($service->id, Logstash::getApiDocInfo($service));
                    },
                    'factory'         => function ($config){
                        return new Logstash($config);
                    },
                ])
            );
        });

        \Event::subscribe(new LoggingEventHandler());
    }
}