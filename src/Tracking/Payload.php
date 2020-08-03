<?php
declare(strict_types=1);


namespace Prime\Tracking;

/**
 * Class Payload
 * @package Prime\Tracking
 */
abstract class Payload implements \JsonSerializable
{
    public $properties = array();
    public $itemType = "";
    public $id = "";

    /**
     * Source constructor.
     * @param string $itemType
     * @param string $itemId
     * @param array $properties
     */
    public function __construct(string $itemType, string $itemId, array $properties)
    {
        $this->itemType = $itemType;
        $this->id = $itemId;
        $this->properties = $properties;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $json = [
            'itemType' => $this->itemType,
            'itemId'   => $this->id,
        ];

        if (count($this->properties) > 0) {
            $json['properties'] = $this->properties;
        }

        return $json;
    }

}
