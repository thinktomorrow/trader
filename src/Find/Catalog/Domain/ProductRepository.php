<?php

namespace Thinktomorrow\Trader\Find\Catalog\Domain;

use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;

interface ProductRepository
{
    public function findById(ProductId $productId, ChannelId $channel, LocaleId $locale): Product;
}
