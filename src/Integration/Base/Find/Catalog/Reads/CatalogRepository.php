<?php

namespace Thinktomorrow\Trader\Integration\Base\Find\Catalog\Reads;

use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CatalogRepository
{
    public function channel(ChannelId $channel);

    public function locale(LocaleId $locale);

    public function paginate(int $perPage): CatalogRepository;

    public function sortByPrice(): CatalogRepository;

    public function sortByPriceDesc(): CatalogRepository;

    public function getAll(): LengthAwarePaginator;

    public function findById($id): ProductRead;
}
