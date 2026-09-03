<?php

namespace Tempcord\Tests\Fixtures;

final class OuterMiddleware extends TrailMiddleware
{
    public function __construct()
    {
        parent::__construct('outer');
    }
}
