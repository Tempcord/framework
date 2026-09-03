<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a class or method as the handler for a select menu choice.
 *
 * Covers every select type — string, user, role, mentionable and channel — since
 * Discord reports them all the same way. The handler may take a $values array or
 * a $value string to receive what the user picked.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class SelectMenu
{
    /**
     * @param string|BackedEnum|null $id the menu's custom id, which may carry
     *        {placeholders}. Defaults to the class name with a SelectMenu prefix
     *        or suffix stripped and the rest snake_cased.
     * @param list<\Tempcord\Interfaces\Middleware|class-string<\Tempcord\Interfaces\Middleware>> $middleware
     *        run in the order given, outermost first; any of them may
     *        answer instead of letting the handler run
     */
    public function __construct(
        public string|BackedEnum|null $id = null,
        public array $middleware = [],
    ) {}
}
