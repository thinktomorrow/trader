<?php

namespace Thinktomorrow\Trader\Purchase\Items\Domain;

use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;

interface PurchasableItemRepository
{
    public function findById(PurchasableItemId $purchasableItemId, ChannelId $channel, LocaleId $locale): PurchasableItem;
}
