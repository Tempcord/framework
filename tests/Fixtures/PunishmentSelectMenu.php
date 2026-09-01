<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\SelectMenu;

#[SelectMenu]
final class PunishmentSelectMenu
{
    /** @var list<array{?string, list<string>}> */
    public static array $calls = [];

    /**
     * @param list<string> $values
     */
    public function __invoke(?string $value, array $values): void
    {
        self::$calls[] = [$value, $values];
    }
}
