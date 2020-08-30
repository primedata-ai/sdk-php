<?php
declare(strict_types=1);

namespace Prime;


use Carbon\Carbon;
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
     * @var PrimeConfig
     */

    private $config;
    /**
     * @var HttpClient
     */
    private $httpClient;

    const EventEndpoint = "/smile";
    const ContextEndpoint = "/context";

    /**
     * Client constructor.
     * @param PrimeConfig $config
     * @param QueueBuffer|null $buffer
     * @param HttpClient|null $httpClient
     */
    public function __construct(PrimeConfig $config, QueueBuffer $buffer = null,
                                HttpClient $httpClient = null

    )
    {
        $this->config = $config;
        $this->buffer = $buffer;
        if ($httpClient == null) {
            $httpClient = new HttpClient(
                [
                    'base_uri' => $config->getHost(),
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
        $payload = new Event($eventName, $this->config->getSourceID(), $properties);
        foreach ($any as $opt) {
            if (is_callable($opt)) {
                $opt($payload);
            }
        }
        if ($payload->source == null) {
            Event::withSource(new Source("s2s", $this->config->getSourceID(), []))($payload);
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
        $payload = new Event("sync-user", $this->config->getSourceID(), $properties);
        Event::withSource(new Source("s2s", $this->config->getSourceID(), []))($payload);
        $this->enqueue($payload);
    }

    /**
     * @param Event $msg
     */
    public function sync(Event $msg)
    {
        $body = $msg->jsonSerialize();
        $body['sendAt'] = Carbon::now()->toIso8601String();
        $endpoint = self::EventEndpoint;
        if ($msg->eventName == "sync-user") {
            $endpoint = self::ContextEndpoint;
        }
        $response = $this->httpClient->post(
            $endpoint,
            [
                RequestOptions::JSON => $body,
                'headers'            => [
                    'X-Client-Id'           => $this->config->getSourceID(),
                    'X-Client-Access-Token' => $this->config->getWriteKey(),
                    'User-Agent'            => 'Prime-PHP/0.0.1; (+https://www.primedata.ai/)'
                ]
            ]);
        $response->getStatusCode();
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
