<?php

namespace Tempcord\Tests\Doubles;

use Tempcord\Discord\Gateway\Connection;
use Tempcord\Discord\Gateway\Helpers\RequestGuildMembersBuilder;

/**
 * A gateway connection that records what the bot sends up it instead of
 * opening a socket.
 */
final class RecordingConnection extends Connection
{
    /** @var list<RequestGuildMembersBuilder> */
    public array $memberRequests = [];

    public function requestGuildMembers(RequestGuildMembersBuilder $request): void
    {
        $this->memberRequests[] = $request;
    }
}
