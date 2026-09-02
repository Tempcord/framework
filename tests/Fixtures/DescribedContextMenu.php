<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Attributes\Command;

#[Command(name: 'Опис', description: 'Discord has nowhere to show this', type: ApplicationCommandTypes::USER)]
final class DescribedContextMenu
{
    public function __invoke(): void {}
}
