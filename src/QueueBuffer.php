<?php
declare(strict_types=1);


namespace Prime;

/**
 * Interface QueueBuffer
 * @package Prime
 */
interface QueueBuffer
{
    /**
     * @param string $topic
     * @param \JsonSerializable $msg
     * @return mixed
     */
    public function sendMessage(string $topic, \JsonSerializable $msg);
}
