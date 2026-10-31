<?php

namespace Tempcord\Runtime\Boot;

enum OutcomeLevel: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
}
