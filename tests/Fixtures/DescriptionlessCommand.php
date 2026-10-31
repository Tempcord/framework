<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;

#[Command]
final class DescriptionlessCommand
{
    public function __invoke(): void {}
}
