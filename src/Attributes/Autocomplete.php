<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a method as the source of suggestions for one of the command's
 * options.
 *
 * The lightest way to suggest values: no separate class, and the method sits on
 * the command it belongs to, with that command's dependencies already to hand.
 * Reach for a class implementing Autocomplete when the same suggestions are
 * wanted by more than one command.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Autocomplete
{
    /**
     * @param string|BackedEnum $option the name of the option this completes,
     *        as Discord knows it — the parameter's name, or whatever
     *        #[Option(name: ...)] renamed it to
     */
    public function __construct(
        public string|BackedEnum $option,
    ) {}
}
