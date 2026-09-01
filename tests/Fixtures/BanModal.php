<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\ModalSubmit;

#[ModalSubmit(id: 'ban.{member}')]
final class BanModal
{
    /** @var list<array{string, ?string, string}> */
    public static array $calls = [];

    public function __invoke(string $member, ?string $reason, string $duration = 'forever'): void
    {
        self::$calls[] = [$member, $reason, $duration];
    }
}
