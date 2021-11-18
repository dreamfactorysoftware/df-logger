<?php
namespace DreamFactory\Core\Logger\Handlers\Events;

use Illuminate\Contracts\Events\Dispatcher;
use DreamFactory\Core\Events\ServiceAssignedEvent;
use DreamFactory\Core\Logger\Services\BaseService as BaseLogService;
use Log;

class LoggingEventHandler
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            [
                ServiceAssignedEvent::class,
            ],
            static::class . '@handleSubEvent'
        );
    }

    /**
     * @param ServiceAssignedEvent $event
     */
    public function handleSubEvent($event)
    {
        // When a service is called, it fires two events, and we only want one for outputting to logstash. so if the event
        // is a call to a generic endpoint, we will stop it.
        if (!preg_match('[{table_name}|{procedure_name}|{function_name}]', $event->name)) {
            $service = $event->getService();
            if ($service instanceof BaseLogService) {
                if ($service->isActive()) {
                    $record = $event->getData();
                    $data = json_decode(array_get($record, 'data'), true);
                    $defaultLevel = 'INFO';
                    $defaultMessage = $event->name;

                    if (!empty($data) && is_array($data)) {
                        $level = strtoupper(array_get($data, 'level'));
                        $level = (empty($level)) ? $defaultLevel : $level;
                        $message = array_get($data, 'message');
                        $message = (empty($message)) ? $defaultMessage : $message;
                    }

                    $eventData = $event->makeData();
                    $allContext = [
                        '_event'    => [
                            'request'  => array_get($eventData, 'request'),
                            'resource' => array_get($eventData, 'resource'),
                            'response' => array_get($eventData, 'response')
                        ],
                        '_platform' => $service->getPlatformInfo()
                    ];
                    array_set($allContext, '_event.response.content_type', 'application/json');
                    $service->setContextByKeys(null, $allContext);
                    $service->log($level, $message);
                    Log::debug('Logged message on [' . $event->name . '] event.');
                }
            }
        }
    }
}