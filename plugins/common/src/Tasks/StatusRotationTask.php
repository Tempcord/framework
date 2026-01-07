<?php

declare(strict_types=1);

namespace Tempcord\Common\Tasks;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Rest\Helpers\Activity\ActivityBuilder;
use Tempcord\Attributes\Task;
use Tempcord\Common\CommonConfig;
use Tempest\Log\Logger;

/**
 * Task that rotates the bot's Discord activity/status.
 *
 * Configure statuses in CommonConfig:
 * ```php
 * $config->withStatusRotation([
 *     ['type' => 0, 'name' => 'with commands'],  // Playing
 *     ['type' => 2, 'name' => 'to your feedback'], // Listening
 *     ['type' => 3, 'name' => 'the server grow'], // Watching
 * ], interval: 300);
 * ```
 */
final class StatusRotationTask
{
    private int $currentIndex = 0;

    public function __construct(
        private readonly Discord $discord,
        private readonly CommonConfig $config,
        private readonly Logger $logger,
    ) {}

    #[Task(interval: 300, name: 'status_rotation', runOnBoot: true)]
    public function rotate(): void
    {
        $statuses = $this->config->statusRotation;

        if (empty($statuses)) {
            return;
        }

        $status = $statuses[$this->currentIndex];
        $this->currentIndex = ($this->currentIndex + 1) % count($statuses);

        try {
            $activity = ActivityBuilder::new()
                ->setName($status['name'])
                ->setType($status['type'] ?? 0);

            if (isset($status['url'])) {
                $activity->setUrl($status['url']);
            }

            $this->discord->gateway->updatePresence(
                activities: [$activity],
            );

            $this->logger->debug("Status rotated to: {$status['name']}");
        } catch (\Throwable $e) {
            $this->logger->error("Failed to rotate status: {$e->getMessage()}");
        }
    }
}
