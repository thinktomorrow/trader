<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use Thinktomorrow\Trader\Catalog\Products\Ports\DefaultProductStateMachine;

trait UsingProductState
{
    private static $stateKey = 'state';

    public function getProductState(): string
    {
        return (string) $this->getState(static::$stateKey);
    }

    public function setProductState($state): void
    {
        $this->state = $state;
    }

    public function getProductStateAttribute(): string
    {
        return static::$stateKey;
    }

    public function isAvailable(): bool
    {
        return ProductState::fromString($this->getProductState())->isAvailable();
    }

    public function scopeAvailable($query): void
    {
        $query->whereIn($this->getProductStateAttribute(), DefaultProductStateMachine::getAvailableStates());
    }

    public function getState(string $key): string
    {
        return $this->$key ?? ProductState::AVAILABLE;
    }

    public function changeState(string $key, $state): void
    {
        $this->$key = $state;
    }
}
