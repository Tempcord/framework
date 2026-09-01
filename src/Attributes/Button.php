<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a class or method as the handler for a button press.
 *
 * Written on a class, __invoke answers the button; written on a method, that
 * method does, so related buttons can share one class. It is repeatable, so a
 * single handler may answer several ids.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Button
{
    /**
     * @param string|BackedEnum|null $id the button's custom id. It may carry
     *        {placeholders}, as in "tournament.accept.{team}", which are matched
     *        out of the incoming id and passed to same-named parameters.
     *        Defaults to the class name with a Button prefix or suffix stripped
     *        and the rest snake_cased.
     */
    public function __construct(
        public string|BackedEnum|null $id = null,
    ) {}
}
