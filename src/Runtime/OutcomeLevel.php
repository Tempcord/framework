<?php

namespace Tempcord\Runtime;

enum OutcomeLevel: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
}
