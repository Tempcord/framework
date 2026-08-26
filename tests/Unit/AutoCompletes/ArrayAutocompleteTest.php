<?php

namespace Tempcord\Tests\Unit\AutoCompletes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\AutoCompletes\ArrayAutocomplete;

#[CoversClass(ArrayAutocomplete::class)]
final class ArrayAutocompleteTest extends BaseTestCase
{
    private function interaction(): CommandInteraction
    {
        return $this->createStub(CommandInteraction::class);
    }

    public function test_it_filters_items_by_substring(): void
    {
        $autocomplete = new ArrayAutocomplete(['apple', 'banana', 'grape']);

        $this->assertSame([0 => 'apple', 2 => 'grape'], $autocomplete->handle($this->interaction(), 'ap'));
    }

    public function test_it_reindexes_when_declared_as_a_list(): void
    {
        $autocomplete = new ArrayAutocomplete(['apple', 'banana', 'grape'], isList: true);

        $this->assertSame(['apple', 'grape'], $autocomplete->handle($this->interaction(), 'ap'));
    }

    public function test_it_preserves_keys_of_an_associative_map(): void
    {
        $autocomplete = new ArrayAutocomplete(['Apple' => 'apple', 'Banana' => 'banana']);

        $this->assertSame(['Banana' => 'banana'], $autocomplete->handle($this->interaction(), 'nan'));
    }

    public function test_an_empty_needle_matches_everything(): void
    {
        $autocomplete = new ArrayAutocomplete(['apple', 'banana'], isList: true);

        $this->assertSame(['apple', 'banana'], $autocomplete->handle($this->interaction(), ''));
    }

    public function test_it_returns_nothing_when_no_item_matches(): void
    {
        $autocomplete = new ArrayAutocomplete(['apple', 'banana'], isList: true);

        $this->assertSame([], $autocomplete->handle($this->interaction(), 'zzz'));
    }
}
