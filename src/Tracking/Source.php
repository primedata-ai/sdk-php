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
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        $json['scope'] = $this->scope;
        return $json;
    }
}
