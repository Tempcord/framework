<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Plugins\Plugin;
use Tempcord\Tempcord;

final class RecordingPlugin implements Plugin
{
    /** @var list<Tempcord> */
    public static array $booted = [];

    public function boot(Tempcord $tempcord): void
    {
        self::$booted[] = $tempcord;
    }
}
