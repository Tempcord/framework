<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Command;

#[Command(description: 'test')]
class InvokableCommand
{

    public function __invoke()
    {

    }

}