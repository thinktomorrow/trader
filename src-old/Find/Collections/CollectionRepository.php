<?php
declare(strict_types=1);

namespace Find\Collections;

use Find\Channels\ChannelId;
use Common\Domain\Locales\LocaleId;
use Thinktomorrow\Trader\Find\Collections\CatalogRepository;

interface CollectionRepository
{
    public function channel(ChannelId $channel);

    public function locale(LocaleId $locale);

    public function filterByCollection();

    public function paginate(int $perPage): CatalogRepository;
}
