<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupState;
use Thinktomorrow\Trader\Catalog\Products\Ports\DefaultProductGroupStateMachine;

trait UsingProductGroupState
{
    private static $stateKey = 'state';

    public function getProductGroupState(): string
    {
        return (string) $this->getState(static::$stateKey);
    }

    public function setProductGroupState($state): void
    {
        $this->state = $state;
    }

    private function getProductGroupStateAttribute(): string
    {
        return static::$stateKey;
    }

    public function isOnline(): bool
    {
        // TODO: use interface contract instead
        return app(DefaultProductGroupStateMachine::class)->isOnline($this);
    }

    public function scopeOnline($query): void
    {
        $query->whereIn($this->getTable() . '.' . $this->getProductGroupStateAttribute(), DefaultProductGroupStateMachine::getOnlineStates());
    }

    public function getState(string $key): string
    {
        return $this->$key ?? ProductGroupState::DRAFT;
    }

    public function changeState(string $key, $state): void
    {
        $this->$key = $state;
    }
}
