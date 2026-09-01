<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Plugins\Plugin;
use Tempcord\Tempcord;

/**
 * Counts how many times it is built, so a test can tell discovery apart from
 * booting.
 */
final class CountingPlugin implements Plugin
{
    public static int $constructed = 0;

    public function __construct()
    {
        self::$constructed++;
    }

    public function boot(Tempcord $tempcord): void {}
}
