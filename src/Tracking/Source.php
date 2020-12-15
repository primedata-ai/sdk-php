<?php
declare(strict_types=1);

namespace Prime\Tracking;

/**
 * Class Source
 * @package Prime\Tracking
 */
class Source extends Payload
{
    public $scope;

    /**
     * Source constructor.
     * @param string $itemType
     * @param string $itemId
     * @param array $properties
     */
    public function __construct(string $itemType, string $itemId, array $properties)
    {
        parent::__construct($itemType, $itemId, $properties);
        $this->scope = $itemId;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        $json['scope'] = $this->scope;
        return $json;
    }
}
