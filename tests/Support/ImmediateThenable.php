<?php

namespace Tests\Support;

use Tempcord\Interfaces\Thenable;

class ImmediateThenable implements Thenable
{
    public function __construct(private mixed $result) {}
    public function then(callable $onFulfilled): void { $onFulfilled($this->result); }
}

