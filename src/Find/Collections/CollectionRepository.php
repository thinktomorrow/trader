<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Collections;

use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;

interface CollectionRepository
{
    public function channel(ChannelId $channel);

    public function locale(LocaleId $locale);

    public function filterByCollection();

    public function paginate(int $perPage): CatalogRepository;
}
