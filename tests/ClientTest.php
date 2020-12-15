<?php

namespace Tests;

use Carbon\Carbon;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client as HttpClient;
use Prime\Client;
use PHPUnit\Framework\TestCase;
use Prime\PrimeConfig;
use Prime\QueueBuffer;
use Prime\Tracking\Event;
use Prime\Tracking\Source;
use Mockery;
use Prime\Tracking\Target;

class ClientTest extends TestCase
{
    /**
     * @var Carbon|false
     */
    private $now;

    protected function setUp()
    {
        parent::setUp();
        $this->now = Carbon::now();
    }

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
                new Target('analyticsUser', 'paul-id', ['id' => 'paul-id', 'age' => 30]),
                $msg->target);
            self::assertEquals('identify', $msg->eventName);
            return true;
        }));
        $client = new Client(new PrimeConfig('s-1', 'w-1'), $buffer);
        $client->identify('paul-id', ['age' => 30]);
    }

    public function testTrackWithBuffer()
    {
        $buffer = Mockery::mock(QueueBuffer::class);
        $buffer->shouldReceive('sendMessage')->with('primedata-events', Mockery::on(function (Event $msg) {
            self::assertEquals([
                'sessionId' => 's-id',
                'events'    => [
                    [
                        'scope'      => 's-1',
                        'eventType'  => 'access_report',
                        'itemType'   => 'event',
                        'profileId'  => 'p-id',
                        'timeStamp'  => $this->now->toIso8601String(),
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
                ]
            ], $msg->jsonSerialize());
            return true;
        }));
        $client = new Client(new PrimeConfig('s-1', 'w-1'), $buffer);
        $client->track('access_report', ['in' => 'the morning'],
            Event::withSessionID("s-id"),
            Event::withProfileID('p-id'),
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
                    'profileId'  => 'u-id',
                    'timeStamp'  => $this->now->toIso8601String(),
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
        $client = new Client(new PrimeConfig('s-1', 'w-1'), $buffer);
        $client->track('access_report', ['in' => 'the morning'],
            Event::withProfileID('u-id'),
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

        $client = new Client(new PrimeConfig('s-1', 'w-1'), null, new HttpClient(['handler' => $handlerStack]));
        $client->track('access_report', ['in' => 'the morning'],
            Event::withProfileID('u-id'),
            Event::withSessionID("s-id"),
            Event::withSource(new Source("site", "primedata.ai", array("price" => 20))),
            Event::withTarget(new Target("report", "CDP solution", array("pages" => 100)))
        );
        $this->assertCount(1, $container);
        $request = $container[0]['request']; # GuzzleHttp\Psr7\Request
        $this->assertEquals([
            'sessionId' => 's-id',
            'sendAt'    => $this->now->toIso8601String(),
            'events'    => [
                [
                    'scope'      => 's-1',
                    'eventType'  => 'access_report',
                    'itemType'   => 'event',
                    'profileId'  => 'u-id',
                    'timeStamp'  => $this->now->toIso8601String(),
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
            ]
        ], json_decode((string)$request->getBody(), JSON_OBJECT_AS_ARRAY));
        $this->assertEquals(['s-1'], $request->getHeader('X-Client-Id'));
        $this->assertEquals(['w-1'], $request->getHeader('X-Client-Access-Token'));
    }
}
