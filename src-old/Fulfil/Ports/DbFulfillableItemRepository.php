<?php
declare(strict_types=1);

namespace Fulfil\Ports;

use Fulfil\Domain\FulfillableItem;
use Fulfil\Domain\FulfillableItemId;
use Fulfil\Domain\FulfillableItemRepository;

class DbFulfillableItemRepository implements FulfillableItemRepository
{
    public function findById(FulfillableItemId $fulfillableItemId): FulfillableItem
    {
        // retrieve paid cart...
    }
}
