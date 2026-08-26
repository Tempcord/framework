<?php

namespace Tempcord\Tools;

use RuntimeException;

final readonly class DocsGenerator
{
    public function __construct(
        private string $root,
        private ApiReflector $reflector = new ApiReflector(),
        private MarkdownWriter $markdown = new MarkdownWriter(),
        private JsonWriter $json = new JsonWriter(),
    ) {}

    /**
     * Everything the generator would write, keyed by path relative to the
     * output directory.
     *
     * Returned rather than written so the same work can be checked against what
     * is committed without touching the filesystem.
     *
     * @return array<string, string>
     */
    public function generate(): array
    {
        $groups = $this->reflector->reflect();
        $files = [];

        foreach ($groups as $symbols) {
            foreach ($symbols as $symbol) {
                $files[$symbol->slug . '.md'] = $this->markdown->render($symbol);
            }
        }

        $files['reference/index.md'] = $this->markdown->renderIndex($groups);

        $guides = $this->guides($files);

        $files['README.md'] = $this->markdown->renderLanding($groups, $guides);
        $files['index.json'] = $this->json->render($groups, $guides);

        return $files;
    }

    /**
     * @param array<string, string> $files
     * @return list<array{title: string, slug: string}>
     */
    private function guides(array &$files): array
    {
        $compiler = new GuideCompiler($this->root);
        $sources = glob($this->root . '/tools/guides/*.md') ?: [];
        sort($sources);

        $guides = [];

        foreach ($sources as $source) {
            $name = basename($source, '.md');
            $body = (string) file_get_contents($source);

            $files['guides/' . $name . '.md'] = $compiler->compile($body);

            $guides[] = [
                'title' => $this->titleOf($body, $name),
                'slug' => 'guides/' . $name,
            ];
        }

        return $guides;
    }

    private function titleOf(string $body, string $fallback): string
    {
        return preg_match('/^#\s+(.+)$/m', $body, $match) === 1 ? trim($match[1]) : $fallback;
    }

    public function write(string $outputDirectory): void
    {
        foreach ($this->generate() as $path => $contents) {
            $absolute = $outputDirectory . '/' . $path;
            $directory = dirname($absolute);

            if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
                throw new RuntimeException('Could not create ' . $directory);
            }

            file_put_contents($absolute, $contents);
        }
    }
}
