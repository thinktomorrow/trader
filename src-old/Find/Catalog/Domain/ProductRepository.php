<?php

namespace Find\Catalog\Domain;

use Find\Channels\ChannelId;
use Common\Domain\Locales\LocaleId;

interface ProductRepository
{
    public function findById(ProductId $productId, ChannelId $channel, LocaleId $locale): Product;
}
