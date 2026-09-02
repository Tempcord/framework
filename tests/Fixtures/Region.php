<?php

namespace Tempcord\Tests\Fixtures;

/**
 * A plain backed enum, which has no say in how its cases read.
 */
enum Region: int
{
    case Europe = 1;
    case NorthAmerica = 2;
}
