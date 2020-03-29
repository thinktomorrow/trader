<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Fulfil\Domain;

interface FulfillableItemRepository
{
    public function findById(FulfillableItemId $fulfillableItemId): FulfillableItem;
}
