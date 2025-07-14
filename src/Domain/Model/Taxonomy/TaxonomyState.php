<?php

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

enum TaxonomyState: string
{
    case online = 'online';
    case offline = 'offline';
    case queued_for_deletion = 'queued_for_deletion';

    public static function onlineStates(): array
    {
        return [
            self::online,
        ];
    }
}
