<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a class or method as the handler for a submitted modal.
 *
 * Beyond {placeholders} from the id, a parameter named after a field's custom id
 * receives what the user typed into that field.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class ModalSubmit
{
    /**
     * @param string|BackedEnum|null $id the modal's custom id, which may carry
     *        {placeholders}. Defaults to the class name with a ModalSubmit or
     *        Modal prefix or suffix stripped and the rest snake_cased.
     */
    public function __construct(
        public string|BackedEnum|null $id = null,
    ) {}
}
