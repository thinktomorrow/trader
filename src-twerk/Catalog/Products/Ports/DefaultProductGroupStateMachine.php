<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupState;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductGroupPublished;
use Thinktomorrow\Trader\Common\State\AbstractStateMachine;
use function event;

class DefaultProductGroupStateMachine extends AbstractStateMachine implements ProductGroupStateMachine
{
    protected function getStateKey(): string
    {
        return ProductGroupState::$KEY;
    }

    public function isOnline(): bool
    {
        return in_array($this->getState(), static::getOnlineStates());
    }

    public static function getOnlineStates(): array
    {
        return [
            ProductGroupState::ONLINE,
        ];
    }

    protected function getStates(): array
    {
        $reflection = new \ReflectionClass(ProductGroupState::class);

        return array_values($reflection->getConstants());
    }

    protected function getTransitions(): array
    {
        return [
            'publish' => [
                'from' => [ProductGroupState::DRAFT],
                'to' => ProductGroupState::ONLINE,
            ],
            'unpublish' => [
                'from' => [ProductGroupState::ONLINE],
                'to' => ProductGroupState::DRAFT,
            ],
            'archive' => [
                'from' => [ProductGroupState::ONLINE],
                'to' => ProductGroupState::ARCHIVED,
            ],
            'revive' => [
                'from' => [ProductGroupState::ARCHIVED],
                'to' => ProductGroupState::ONLINE,
            ],
        ];
    }

    public function emitEvent(string $transition): void
    {
        if ($transition === 'publish') {
            event(new ProductGroupPublished((string) $this->object->id));
        }
    }
}
