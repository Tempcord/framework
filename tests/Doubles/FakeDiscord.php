<?php

namespace Tempcord\Tests\Doubles;

use Psr\Log\NullLogger;
use CyberWolf\Discord\DataMapper;
use CyberWolf\Discord\Discord;
use CyberWolf\Discord\EventHandler;
use CyberWolf\Discord\Gateway\Connection;
use CyberWolf\Discord\Gateway\Helpers\RequestGuildMembersBuilder;
use CyberWolf\Discord\Rest\Rest;
use ReflectionClass;

/**
 * A Discord instance wired to a real Rest over a recording transport, and to a
 * gateway connection that emits but never opens a socket.
 */
final class FakeDiscord extends Discord
{
    /**
     * The same object as $this->gateway, kept under its own type because
     * Discord declares that property as a plain Connection.
     */
    public readonly RecordingConnection $recordingGateway;

    public function __construct(public readonly RecordingHttp $http)
    {
        $this->rest = new Rest($this->http, new DataMapper(new NullLogger()), new NullLogger());

        /*
         * Connection's constructor builds a websocket shard, which a unit test
         * has no use for. Only its event emitter, and what the bot sends back
         * up the gateway, matter here.
         */
        $gateway = new ReflectionClass(RecordingConnection::class)->newInstanceWithoutConstructor();
        $gateway->events = new EventHandler(new DataMapper(new NullLogger()));

        $this->gateway = $gateway;
        $this->recordingGateway = $gateway;
    }

    /**
     * @return list<RequestGuildMembersBuilder>
     */
    public function memberRequests(): array
    {
        return $this->recordingGateway->memberRequests;
    }
}
