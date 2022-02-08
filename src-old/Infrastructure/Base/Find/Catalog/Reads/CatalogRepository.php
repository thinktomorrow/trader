<?php

namespace Base\Find\Catalog\Reads;

use Find\Channels\ChannelId;
use Common\Domain\Locales\LocaleId;
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
