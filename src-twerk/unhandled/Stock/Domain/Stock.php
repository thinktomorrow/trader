<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Stock\Domain;

class Stock
{
    private $stocks = [];

    public function add(LocationId $locationId, int $quantity): void
    {
        $this->stocks[$locationId->get()] = $quantity;
    }

    public function exists(LocationId $locationId): bool
    {
        return isset($this->stocks[$locationId->get()]);
    }

    public function at(LocationId $locationId): int
    {
        return $this->stocks[$locationId->get()];
    }
}
