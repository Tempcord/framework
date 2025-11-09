<?php

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\Intent;

return new \Tempcord\TempcordConfig(
    token: \Tempest\env('DISCORD_TOKEN'),
    intents: Bitwise::from(
        Intent::GUILD_MESSAGES,
        Intent::DIRECT_MESSAGES,
        Intent::MESSAGE_CONTENT
    )
);