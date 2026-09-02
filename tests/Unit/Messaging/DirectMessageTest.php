<?php

namespace Tempcord\Tests\Unit\Messaging;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Rest\Helpers\Channel\MessageBuilder;
use Tempcord\Messaging\DirectMessage;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Unit\TestCase;

use function React\Async\async;
use function React\Async\await;

#[CoversClass(DirectMessage::class)]
final class DirectMessageTest extends TestCase
{
    private const string USER = '254766810296090626';

    private RecordingHttp $http;

    private RecordingLogger $logger;

    private function directMessage(string ...$refusing): DirectMessage
    {
        $this->http = new RecordingHttp(failPostsMatching: $refusing);
        $this->logger = new RecordingLogger();

        return new DirectMessage(new FakeDiscord($this->http), $this->logger);
    }

    /**
     * The REST calls are awaited, so this runs inside a fiber exactly as the
     * dispatcher runs a handler.
     */
    private function send(DirectMessage $dm, MessageBuilder|string $message): bool
    {
        return await(async(static fn() => $dm->send(self::USER, $message))());
    }

    private function posted(string $needle): array
    {
        return array_values(array_filter(
            $this->http->posts,
            static fn(array $post) => str_contains($post['url'], $needle),
        ));
    }

    public function test_it_opens_a_private_channel_and_writes_to_it(): void
    {
        $dm = $this->directMessage();

        $sent = $this->send($dm, MessageBuilder::new()->setContent('You have been warned.'));

        $this->assertTrue($sent);
        $this->assertNotSame([], $this->posted('users/@me/channels'));
        $this->assertNotSame([], $this->posted('messages'));
    }

    /**
     * Most of what a bot says privately is one line, and building a message for
     * it says nothing the string does not.
     */
    public function test_a_plain_string_is_sent_as_the_content(): void
    {
        $dm = $this->directMessage();

        $this->send($dm, 'You have been warned.');

        $this->assertSame('You have been warned.', $this->posted('messages')[0]['content']['content']);
    }

    /**
     * A member with closed DMs is an ordinary state of affairs, not a failure:
     * letting it throw would abandon whatever the caller was in the middle of,
     * which is usually the punishment the message was only announcing.
     */
    public function test_a_member_who_cannot_be_reached_is_reported_rather_than_thrown_at(): void
    {
        $dm = $this->directMessage('users/@me/channels');

        $this->assertFalse($this->send($dm, 'You have been warned.'));
    }

    public function test_a_message_refused_after_the_channel_opened_is_also_reported(): void
    {
        $dm = $this->directMessage('messages');

        $this->assertFalse($this->send($dm, 'You have been warned.'));
        $this->assertNotSame([], $this->posted('users/@me/channels'));
    }

    /**
     * Closed DMs say nothing about the health of the bot, so reporting them as
     * errors would only teach whoever reads the log to ignore it.
     */
    public function test_being_unable_to_reach_someone_is_noted_but_not_as_an_error(): void
    {
        $dm = $this->directMessage('users/@me/channels');

        $this->send($dm, 'You have been warned.');

        $this->assertNotSame([], array_filter(
            $this->logger->messages,
            static fn(string $message) => str_contains($message, self::USER),
        ));
        $this->assertSame(['info'], array_values(array_unique($this->logger->levels)));
    }
}
