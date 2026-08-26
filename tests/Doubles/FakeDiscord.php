<?php

namespace Tempcord\Tests\Doubles;

use Psr\Log\NullLogger;
use CyberWolf\Discord\DataMapper;
use CyberWolf\Discord\Discord;
use CyberWolf\Discord\EventHandler;
use CyberWolf\Discord\Gateway\Connection;
use CyberWolf\Discord\Rest\Rest;
use ReflectionClass;

/**
 * A Discord instance wired to a real Rest over a recording transport, and to a
 * gateway connection that emits but never opens a socket.
 */
final class FakeDiscord extends Discord
{
    public function __construct(public readonly RecordingHttp $http)
    {
        $this->rest = new Rest($this->http, new DataMapper(new NullLogger()), new NullLogger());

        /*
         * Connection's constructor builds a websocket shard, which a unit test
         * has no use for. Only its event emitter matters here.
         */
        $gateway = new ReflectionClass(Connection::class)->newInstanceWithoutConstructor();
        $gateway->events = new EventHandler(new DataMapper(new NullLogger()));

        $this->gateway = $gateway;
    }
}
