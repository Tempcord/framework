<?php

namespace Tempcord\Pagination;

use Closure;
use LogicException;
use React\Promise\PromiseInterface;
use Tempcord\Attributes\Button;
use Tempcord\Discord\Component\Button\PrimaryButton;
use Tempcord\Discord\Component\Button\SecondaryButton;
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\Discord\Interaction\Response;
use Tempcord\Discord\Rest\Helpers\Channel\EmbedBuilder;
use Tempest\Container\Singleton;

use function React\Async\await;

/**
 * Turns a page source into a Discord response and owns the short-lived state
 * behind its buttons. Callers supply data and a renderer; component routing,
 * disabled edge buttons, and expired sessions stay here.
 */
#[Singleton]
final class Pagination
{
    private const int DEFAULT_TTL_SECONDS = 900;

    /** @var array<string, PaginationSession> */
    private array $sessions = [];

    /**
     * @param Closure(Page): (string|EmbedBuilder|PromiseInterface) $render
     */
    public function reply(
        Paginator $paginator,
        Closure $render,
        bool $ephemeral = false,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ): InteractionCallbackBuilder {
        if ($ttlSeconds < 1) {
            throw new LogicException('A paginator session must live for at least one second.');
        }

        $this->prune();
        $id = bin2hex(random_bytes(6));
        $session = new PaginationSession($paginator, $render, time() + $ttlSeconds);
        $this->sessions[$id] = $session;

        return $this->response($id, $session, 1, $ephemeral ? Response::ephemeral() : Response::message());
    }

    #[Button(id: 'pagination.{session}.{action}.{page}')]
    public function page(ButtonInteraction $interaction, string $session, string $action, int $page): void
    {
        $this->prune();
        $stored = $this->sessions[$session] ?? null;

        if ($stored === null) {
            $interaction->reply('Ця пагінація вже завершилася. Запустіть команду ще раз.', ephemeral: true);

            return;
        }

        $interaction->createInteractionResponse($this->response($session, $stored, $page, Response::update()));
    }

    private function response(
        string $id,
        PaginationSession $session,
        int $requestedPage,
        InteractionCallbackBuilder $response,
    ): InteractionCallbackBuilder {
        $page = $this->resolve($session->paginator->page($requestedPage));
        $content = $this->resolve(($session->render)($page));

        if (is_string($content)) {
            $response->setContent($content);
        } elseif ($content instanceof EmbedBuilder) {
            $response->addEmbed($content);
        } else {
            throw new LogicException('A pagination renderer must return a string or EmbedBuilder.');
        }

        return $response
            ->addButton(new SecondaryButton($this->buttonId($id, 'first', 1), '«', disabled: !$page->hasPrevious()))
            ->addButton(new SecondaryButton($this->buttonId($id, 'previous', $page->number - 1), '‹', disabled: !$page->hasPrevious()))
            ->addButton(new PrimaryButton($this->buttonId($id, 'current', $page->number), $page->number . ' / ' . $page->totalPages, disabled: true))
            ->addButton(new SecondaryButton($this->buttonId($id, 'next', $page->number + 1), '›', disabled: !$page->hasNext()))
            ->addButton(new SecondaryButton($this->buttonId($id, 'last', $page->totalPages), '»', disabled: !$page->hasNext()));
    }

    private function buttonId(string $session, string $action, int $page): string
    {
        return 'pagination.' . $session . '.' . $action . '.' . max(1, $page);
    }

    private function resolve(mixed $value): mixed
    {
        return $value instanceof PromiseInterface ? await($value) : $value;
    }

    private function prune(): void
    {
        $now = time();

        foreach ($this->sessions as $id => $session) {
            if ($session->expiresAt < $now) {
                unset($this->sessions[$id]);
            }
        }
    }
}
