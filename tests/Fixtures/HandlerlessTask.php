<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Scheduled;

#[Scheduled(everySeconds: 10)]
final class HandlerlessTask
{
}
