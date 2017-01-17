<?php
namespace DreamFactory\Core\Logger\Handlers\Events;

use DreamFactory\Core\Events\QueuedApiEvent;
use DreamFactory\Core\Models\ServiceEventMap;
use Illuminate\Contracts\Events\Dispatcher;
use DreamFactory\Core\Events\ApiEvent;
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
                QueuedApiEvent::class,
            ],
            static::class . '@handleApiEvent'
        );
    }

    /**
     * Handle events.
     *
     * @param ApiEvent $event
     *
     * @return boolean
     */
    public function handleApiEvent($event)
    {
        $eventName = str_replace('.queued', null, $event->name);
        $records = ServiceEventMap::whereEvent($eventName)->get()->all();
        if (empty($records)) {
            // Look for wildcard events by service (example: user.*)
            $serviceName = substr($eventName, 0, strpos($eventName, '.'));
            $wildcardEvent = $serviceName . '.*';
            $records = ServiceEventMap::whereEvent($wildcardEvent)->get()->all();
        }

        foreach ($records as $record) {
            Log::debug('Service event handled: ' . $eventName);
            $service = \ServiceManager::getServiceById($record->service_id);

            // Handle log services.
            if ($service instanceof BaseLogService) {
                if ($service->isActive()) {
                    $data = json_decode($record->data, true);
                    $defaultLevel = 'INFO';
                    $defaultMessage = $eventName;

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
                }
            }
        }
    }
}