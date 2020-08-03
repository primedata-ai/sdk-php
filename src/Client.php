<?php
declare(strict_types=1);

namespace Prime;


use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Prime\Tracking\Event;
use GuzzleHttp\Client as HttpClient;
use Prime\Tracking\Source;

class Client
{
    /**
     * @var QueueBuffer|null
     */
    private $buffer;

    /**
     * @var string
     */
    private $sourceID;
    /**
     * @var string
     */
    private $writeKey;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Client constructor.
     * @param string $sourceID
     * @param string $writeKey
     * @param QueueBuffer|null $buffer
     * @param string|null $host
     * @param HttpClient|null $httpClient
     */
    public function __construct(string $sourceID, string $writeKey,
                                QueueBuffer $buffer = null,
                                string $host = null,
                                HttpClient $httpClient = null
    )
    {
        $this->sourceID = $sourceID;
        $this->writeKey = $writeKey;
        $this->buffer = $buffer;
        if ($host == null or $host == "") {
            $host = 'https://powehi.primedata.ai';
        }
        if ($httpClient == null) {
            $httpClient = new HttpClient(
                [
                    'base_uri' => $host,
                    'timeout'  => 1.0
                ]);
        }
        $this->httpClient = $httpClient;
    }

    /**
     * @param $eventName
     * @param $properties
     * @param mixed ...$any
     */
    public function track($eventName, $properties, ...$any)
    {
        $payload = new Event($eventName, $this->sourceID, $properties);
        foreach ($any as $opt) {
            if (is_callable($opt)) {
                $opt($payload);
            }
        }
        if ($payload->source == null) {
            Event::withSource(new Source("s2s", $this->sourceID, []))($payload);
        }
        $this->enqueue($payload);
    }

    /**
     * @param $userID
     * @param $properties
     */
    public function identify($userID, $properties)
    {
        $properties["user_id"] = $userID;
        $payload = new Event("sync-user", $this->sourceID, $properties);
        Event::withSource(new Source("s2s", $this->sourceID, []))($payload);
        $this->enqueue($payload);
    }

    /**
     * @param Event $msg
     * @throws GuzzleException
     */
    public function sync(Event $msg)
    {
        $this->httpClient->post("/smile",
            [
                RequestOptions::JSON => $msg->jsonSerialize(),
                'headers'            => [
                    'X-Client-Id'           => $this->sourceID,
                    'X-Client-Access-Token' => $this->writeKey,
                    'User-Agent'            => 'Prime-PHP/0.0.1; (+https://www.primedata.ai/)'
                ]
            ]);
    }

    private function enqueue(Event $msg)
    {
        if ($this->buffer != null) {
            $this->buffer->sendMessage("primedata-events", $msg);
            return;
        }
        $this->sync($msg);
    }
}