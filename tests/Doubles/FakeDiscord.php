<?php

namespace Tempcord\Tests\Doubles;

use Psr\Log\NullLogger;
use Ragnarok\Fenrir\DataMapper;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Rest\Rest;

/**
 * A Discord instance wired to a real Rest over a recording transport, so no
 * gateway or network is involved.
 */
final class FakeDiscord extends Discord
{
    public function __construct(public readonly RecordingHttp $http)
    {
        $this->rest = new Rest($this->http, new DataMapper(new NullLogger()), new NullLogger());
    }
}
