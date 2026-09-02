<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Interfaces\Choosable;

enum Platform: string implements Choosable
{
    case PC = 'PC';
    case PlayStation = 'PS4';
    case Xbox = 'X1';

    public function label(): string
    {
        return match ($this) {
            self::PC => 'PC',
            self::PlayStation => 'PlayStation',
            self::Xbox => 'Xbox',
        };
    }
}
