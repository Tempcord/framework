<?php

declare(strict_types=1);

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Components\Button;
use Tempcord\Attributes\Components\Modal;
use Tempcord\Attributes\Components\SelectMenu;
use Tempcord\Registries\ComponentsRegistry;
use Tempest\Container\Container;
use Tempest\Core\Discovery;
use Tempest\Core\DiscoveryLocation;
use Tempest\Core\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;

final class ComponentsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly ComponentsRegistry $registry
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $this->discoverButton($method);
            $this->discoverSelectMenu($method);
            $this->discoverModal($method);
        }
    }

    private function discoverButton(MethodReflector $method): void
    {
        $button = $method->getAttribute(Button::class);

        if ($button === null) {
            return;
        }

        $button->setReflector($method);
        $this->registry->registerButton($button);
    }

    private function discoverSelectMenu(MethodReflector $method): void
    {
        $selectMenu = $method->getAttribute(SelectMenu::class);

        if ($selectMenu === null) {
            return;
        }

        $selectMenu->setReflector($method);
        $this->registry->registerSelectMenu($selectMenu);
    }

    private function discoverModal(MethodReflector $method): void
    {
        $modal = $method->getAttribute(Modal::class);

        if ($modal === null) {
            return;
        }

        $modal->setReflector($method);
        $this->registry->registerModal($modal);
    }

    public function createCachePayload(): string
    {
        return serialize([
            'buttons' => $this->registry->getButtons(),
            'selectMenus' => $this->registry->getSelectMenus(),
            'modals' => $this->registry->getModals(),
        ]);
    }

    public function restoreCachePayload(Container $container, string $payload): void
    {
        $data = unserialize($payload);

        foreach ($data['buttons'] ?? [] as $button) {
            $this->registry->registerButton($button);
        }

        foreach ($data['selectMenus'] ?? [] as $selectMenu) {
            $this->registry->registerSelectMenu($selectMenu);
        }

        foreach ($data['modals'] ?? [] as $modal) {
            $this->registry->registerModal($modal);
        }
    }
}
