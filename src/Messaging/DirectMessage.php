<?php

namespace Tempcord\Messaging;

use Tempcord\Discord\Discord;
use Tempcord\Discord\Rest\Helpers\Channel\MessageBuilder;
use Tempest\Log\Logger;
use Throwable;

use function React\Async\await;

/**
 * Writes to a member privately, on a best-effort basis.
 *
 * A member who has closed their direct messages, who shares no server with the
 * bot any more, or who has blocked it cannot be written to. That is an ordinary
 * state of affairs rather than a failure — and a bot that lets it throw
 * abandons whatever it was in the middle of, which is usually the punishment or
 * the decision the message was only announcing.
 *
 * So this reports whether the message landed instead of throwing, leaving the
 * caller to decide whether it mattered. Reaching a member is almost never a
 * precondition for the thing being announced.
 */
final readonly class DirectMessage
{
    public function __construct(
        private Discord $discord,
        private Logger $logger,
    ) {}

    /**
     * @return bool whether the member could be reached
     */
    public function send(string $userId, MessageBuilder|string $message): bool
    {
        $message = is_string($message)
            ? MessageBuilder::new()->setContent($message)
            : $message;

        try {
            $channel = await($this->discord->rest->user->createDm($userId));

            await($this->discord->rest->channel->createMessage($channel->id, $message));

            return true;
        } catch (Throwable $throwable) {
            /*
             * Logged at info: closed DMs are the common case and say nothing
             * about the health of the bot, so reporting them as errors would
             * only teach whoever reads the log to ignore it.
             */
            $this->logger->info(
                'Could not write to ' . $userId . ': ' . $throwable->getMessage(),
            );

            return false;
        }
    }
}
