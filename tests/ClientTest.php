<?php

namespace Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client as HttpClient;
use Prime\Client;
use PHPUnit\Framework\TestCase;
use Prime\QueueBuffer;
use Prime\Tracking\Event;
use Prime\Tracking\Source;
use Mockery;
use Prime\Tracking\Target;

class ClientTest extends TestCase
{
    const AssertPayload = ['sessionId' => 's-id', 'events' => [
        [
            'scope'      => 's-1',
            'eventType'  => 'access_report',
            'itemType'   => 'event',
            'target'     => [
                'itemType'   => 'report',
                'itemId'     => 'CDP solution',
                'properties' => [
                    'pages' => 100
                ]
            ],
            'source'     => [
                'scope'      => 's-1',
                'itemType'   => 'site',
                'itemId'     => 'primedata.ai',
                'properties' => ['price' => 20]
            ],
            'properties' => ['in' => 'the morning'],
        ]
    ]];

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testIdentity()
    {
        $buffer = Mockery::mock(QueueBuffer::class);
        $buffer->shouldReceive('sendMessage')->with('primedata-events', Mockery::on(function (Event $msg) {
            self::assertEquals(
                ['events' => [
                    [
                        'scope'      => 's-1',
                        'eventType'  => 'sync-user',
                        'itemType'   => 'event',
                        'source'     => [
                            'scope'    => 's-1',
                            'itemType' => 's2s',
                            'itemId'   => 's-1',
                        ],
                        'properties' => ['device' => 'Macbook Pro', 'user_id' => 'paul-id'],
                        'target'     => [],
                    ]
                ]], $msg->jsonSerialize());
            return true;
        }));
        $client = new Client('s-1', 'w-1', $buffer);
        $client->identify('paul-id', ['device' => 'Macbook Pro']);
    }

    public function testTrackWithBuffer()
    {
        $buffer = Mockery::mock(QueueBuffer::class);
        $buffer->shouldReceive('sendMessage')->with('primedata-events', Mockery::on(function (Event $msg) {
            self::assertEquals(self::AssertPayload, $msg->jsonSerialize());
            return true;
        }));
        $client = new Client('s-1', 'w-1', $buffer);
        $client->track('access_report', ['in' => 'the morning'],
            Event::withSessionID("s-id"),
            Event::withSource(new Source("site", "primedata.ai", array("price" => 20))),
            Event::withTarget(new Target("report", "CDP solution", array("pages" => 100)))
        );
    }


    public function testTrackWithBuffer_Without_Source()
    {
        $buffer = Mockery::mock(QueueBuffer::class);
        $buffer->shouldReceive('sendMessage')->with('primedata-events', Mockery::on(function (Event $msg) {
            self::assertEquals(['sessionId' => 's-id', 'events' => [
                [
                    'scope'      => 's-1',
                    'eventType'  => 'access_report',
                    'itemType'   => 'event',
                    'target'     => [
                        'itemType'   => 'report',
                        'itemId'     => 'CDP solution',
                        'properties' => [
                            'pages' => 100
                        ]
                    ],
                    'source'     => [
                        'scope'    => 's-1',
                        'itemType' => 's2s',
                        'itemId'   => 's-1'
                    ],
                    'properties' => ['in' => 'the morning'],
                ]
            ]], $msg->jsonSerialize());
            return true;
        }));
        $client = new Client('s-1', 'w-1', $buffer);
        $client->track('access_report', ['in' => 'the morning'],
            Event::withSessionID("s-id"),
            Event::withTarget(new Target("report", "CDP solution", array("pages" => 100)))
        );
    }

    public function testTrackWithHttpGuzzle()
    {
        $mock = new MockHandler([
            new Response(200, [], ''),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new Client('s-1', 'w-1', null, new HttpClient(['handler' => $handlerStack]));
        $client->track('access_report', ['in' => 'the morning'],
            Event::withSessionID("s-id"),
            Event::withSource(new Source("site", "primedata.ai", array("price" => 20))),
            Event::withTarget(new Target("report", "CDP solution", array("pages" => 100)))
        );
        $this->assertCount(1, $container);
        $request = $container[0]['request']; # GuzzleHttp\Psr7\Request
        $this->assertEquals(self::AssertPayload, json_decode((string)$request->getBody(), JSON_OBJECT_AS_ARRAY));
        $this->assertEquals(['s-1'], $request->getHeader('X-Client-Id'));
        $this->assertEquals(['w-1'], $request->getHeader('X-Client-Access-Token'));
    }
}
