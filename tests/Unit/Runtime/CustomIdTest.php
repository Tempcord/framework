<?php

namespace Tempcord\Tests\Unit\Runtime;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Runtime\CustomId;

#[CoversClass(CustomId::class)]
final class CustomIdTest extends BaseTestCase
{
    public function test_a_pattern_without_placeholders_is_literal(): void
    {
        $id = CustomId::compile('report');

        $this->assertTrue($id->isLiteral());
        $this->assertSame([], $id->parameters);
    }

    public function test_a_literal_matches_only_itself(): void
    {
        $id = CustomId::compile('report');

        $this->assertSame([], $id->match('report'));
        $this->assertNull($id->match('report.extra'));
    }

    public function test_it_reads_a_placeholder_out_of_an_id(): void
    {
        $id = CustomId::compile('tournament.accept.{team}');

        $this->assertFalse($id->isLiteral());
        $this->assertSame(['team'], $id->parameters);
        $this->assertSame(['team' => '42'], $id->match('tournament.accept.42'));
    }

    public function test_a_placeholder_stops_at_the_literal_that_follows_it(): void
    {
        $id = CustomId::compile('poll.{poll}.vote.{option}');

        $this->assertSame(
            ['poll' => '7', 'option' => 'yes'],
            $id->match('poll.7.vote.yes'),
        );
    }

    /**
     * A trailing placeholder has nothing after it to stop at, so it takes the
     * rest of the id even when that rest contains the separator.
     */
    public function test_a_trailing_placeholder_takes_the_remainder(): void
    {
        $this->assertSame(
            ['rest' => 'b.c.d'],
            CustomId::compile('a.{rest}')->match('a.b.c.d'),
        );
    }

    public function test_a_placeholder_does_not_match_an_empty_segment(): void
    {
        $this->assertNull(CustomId::compile('a.{id}')->match('a.'));
    }

    public function test_a_pattern_does_not_match_a_different_id(): void
    {
        $this->assertNull(CustomId::compile('tournament.accept.{team}')->match('tournament.reject.42'));
    }

    /**
     * Anything a pattern's literal part contains is compared as text, so an id
     * built around regex punctuation still behaves.
     */
    public function test_literal_punctuation_is_not_read_as_a_pattern(): void
    {
        $id = CustomId::compile('a.b+c[{value}]');

        $this->assertSame(['value' => '1'], $id->match('a.b+c[1]'));
        $this->assertNull($id->match('axb+c[1]'));
    }

    public function test_it_builds_a_concrete_id_from_values(): void
    {
        $this->assertSame(
            'poll.7.vote.yes',
            CustomId::compile('poll.{poll}.vote.{option}')->build(['poll' => 7, 'option' => 'yes']),
        );
    }

    public function test_building_a_literal_needs_no_values(): void
    {
        $this->assertSame('report', CustomId::compile('report')->build());
    }

    public function test_building_without_a_value_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('needs a value for {team}');

        CustomId::compile('tournament.accept.{team}')->build();
    }

    public function test_a_repeated_placeholder_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('more than once');

        CustomId::compile('a.{id}.b.{id}');
    }
}
