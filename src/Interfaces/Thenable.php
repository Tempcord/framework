<?php

namespace Tempcord\Interfaces;

interface Thenable
{
    /** Register a fulfillment handler; implementations may call immediately or asynchronously */
    public function then(callable $onFulfilled): void;
}