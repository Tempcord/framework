<?php
declare(strict_types=1);

namespace Tempcord {

    use Tempcord\Support\DiscordObjectFactory;
    use function Tempest\get;

    function discord(mixed $value): DiscordObjectFactory
    {
        return get(DiscordObjectFactory::class)?->withData($value);
    }
}