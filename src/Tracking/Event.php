<?php
declare(strict_types=1);


namespace Prime\Tracking;

use Carbon\Carbon;

/**
 * Class Event
 * @package Prime\Tracking
 */
class Event extends Payload
{
    public $eventName = "";
    private $sessionId = "";
    public $source;
    public $target;
    private $scope;
    private $timeStamp;

    public static function withTarget(Target $target)
    {
        return function (event $payload) use ($target) {
            $payload->target = $target;
        };
    }

    public static function withSource(Source $source)
    {
        return function (event $payload) use ($source) {
            $source->scope = $payload->scope;
            $payload->source = $source;
        };
    }

    public static function withSessionID(string $session)
    {
        return function (event $payload) use ($session) {
            $payload->sessionId = $session;
        };
    }

    /**
     * Event constructor.
     * @param string $eventName
     * @param string $scope
     * @param array $properties
     */
    public function __construct(string $eventName, string $scope, array $properties)
    {
        $this->eventName = $eventName;
        $this->scope = $scope;
        if (count($properties) > 0) {
            $this->properties = $properties;
        }
        $this->timeStamp = Carbon::now();
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $payload = [];
        if ($this->sessionId != "") {
            $payload['sessionId'] = $this->sessionId;
        }
        $payload['events'] = [
            [
                'scope'      => $this->scope,
                'eventType'  => $this->eventName,
                'itemType'   => 'event',
                'timeStamp'  => $this->timeStamp->toIso8601String(),
                'properties' => $this->properties,
                'target'     => $this->target == null ? null : $this->target->jsonSerialize(),
                'source'     => $this->source == null ? null : $this->source->jsonSerialize(),
            ]
        ];

        return $payload;
    }
}
