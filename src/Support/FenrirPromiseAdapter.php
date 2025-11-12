<?php

namespace Tempcord\Support;

use Tempcord\Interfaces\Thenable;

class FenrirPromiseAdapter implements Thenable
{
    /** @var mixed */
    private $promise;

    public function __construct($promise)
    {
        $this->promise = $promise;
    }

    public function then(callable $onFulfilled): void
    {
        // Fenrir promises expose then(callable)
        $this->promise->then(function ($result) use ($onFulfilled) {
            $onFulfilled($result);
        });
    }
}