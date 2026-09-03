<?php

namespace Tempcord\Tests\Fixtures;

final class InnerMiddleware extends TrailMiddleware
{
    public function __construct()
    {
        parent::__construct('inner');
    }
}
