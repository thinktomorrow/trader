<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductMarkedAvailable;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductMarkedUnavailable;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductQueuedForDeletion;
use Thinktomorrow\Trader\Common\State\Stateful;
use Thinktomorrow\Trader\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use function event;

class DefaultProductStateMachine extends AbstractStateMachine implements ProductStateMachine
{
    protected function getStateKey(): string
    {
        return ProductState::$KEY;
    }

    protected function getStates(): array
    {
        $reflection = new \ReflectionClass(ProductState::class);

        return array_values($reflection->getConstants());
    }

    protected function getTransitions(): array
    {
        return [
            'mark_available' => [
                'from' => [ProductState::UNAVAILABLE],
                'to' => ProductState::AVAILABLE,
            ],
            'mark_unavailable' => [
                'from' => [ProductState::AVAILABLE],
                'to' => ProductState::UNAVAILABLE,
            ],
            'delete' => [
                'from' => [ProductState::AVAILABLE, ProductState::UNAVAILABLE],
                'to' => ProductState::DELETED,
            ],
            'restore' => [
                'from' => [ProductState::DELETED],
                'to' => ProductState::AVAILABLE,
            ],
        ];
    }

    public function emitEvent(string $transition): void
    {
        if ($transition === 'mark_available') {
            event(new ProductMarkedAvailable((string) $this->object->id));
        }

        if ($transition === 'mark_unavailable') {
            event(new ProductMarkedUnavailable((string) $this->object->id));
        }

        if ($transition === 'delete') {
            event(new ProductQueuedForDeletion((string) $this->object->id));
        }
    }

    public function isAvailable(): bool
    {
        return in_array($this->getState(), static::getAvailableStates());
    }

    public static function getAvailableStates(): array
    {
        return [
            ProductState::AVAILABLE,
        ];
    }
}
