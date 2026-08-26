<?php

namespace Tempcord\Tests\Doubles;

use Discord\Http\Http;
use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Stands in for the concrete Http class fenrir's Rest requires, recording every
 * request so tests can assert which Discord endpoint a command was sent to.
 */
final class RecordingHttp extends Http
{
    /** @var list<array{url: string, content: mixed}> */
    public array $posts = [];

    /** @var list<string> */
    public array $gets = [];

    /** @var list<array{url: string, content: mixed}> */
    public array $puts = [];

    public function __construct(
        private readonly bool $failApplicationLookup = false,
        private readonly array $failPostsMatching = [],
    ) {}

    public function get($url, $content = null, array $headers = []): PromiseInterface
    {
        $this->gets[] = (string) $url;

        if ($this->failApplicationLookup) {
            return reject(new RuntimeException('401: Unauthorized'));
        }

        return resolve((object) [
            'id' => '424242',
            'name' => 'test-bot',
            'description' => '',
            'icon' => null,
        ]);
    }

    public function post($url, $content = null, array $headers = []): PromiseInterface
    {
        $url = (string) $url;

        $this->posts[] = ['url' => $url, 'content' => $content];

        foreach ($this->failPostsMatching as $needle) {
            if (str_contains($url, $needle)) {
                return reject(new RuntimeException('50035: Invalid Form Body'));
            }
        }

        return resolve((object) ['id' => '1', 'name' => 'registered']);
    }

    public function put($url, $content = null, array $headers = []): PromiseInterface
    {
        $url = (string) $url;

        $this->puts[] = ['url' => $url, 'content' => $content];

        foreach ($this->failPostsMatching as $needle) {
            if (str_contains($url, $needle)) {
                return reject(new RuntimeException('50035: Invalid Form Body'));
            }
        }

        return resolve([]);
    }

    /** @return list<string> */
    public function postedUrls(): array
    {
        return array_column($this->posts, 'url');
    }

    /** @return list<string> */
    public function putUrls(): array
    {
        return array_column($this->puts, 'url');
    }
}
