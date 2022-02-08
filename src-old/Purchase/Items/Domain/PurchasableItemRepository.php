<?php

namespace Purchase\Items\Domain;

use Find\Channels\ChannelId;
use Common\Domain\Locales\LocaleId;

interface PurchasableItemRepository
{
    public function findById(PurchasableItemId $purchasableItemId, ChannelId $channel, LocaleId $locale): PurchasableItem;
}
