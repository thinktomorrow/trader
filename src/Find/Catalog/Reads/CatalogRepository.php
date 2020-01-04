<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

interface CatalogRepository
{
    public function channel(string $channel);

    public function locale(string $locale);

    public function getAll(): array;
}
