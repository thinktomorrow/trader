<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Fulfil\Ports;

use Thinktomorrow\Trader\Fulfil\Domain\FulfillableItem;
use Thinktomorrow\Trader\Fulfil\Domain\FulfillableItemId;
use Thinktomorrow\Trader\Fulfil\Domain\FulfillableItemRepository;

class DbFulfillableItemRepository implements FulfillableItemRepository
{
    public function findById(FulfillableItemId $fulfillableItemId): FulfillableItem
    {
        // retrieve paid cart...
    }
}
