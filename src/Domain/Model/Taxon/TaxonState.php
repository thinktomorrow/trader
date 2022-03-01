<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

enum TaxonState: string
{
    case online = 'online';
    case offline = 'offline';
    case queued_for_deletion = 'queued_for_deletion';

    public static function onlineStates(): array
    {
        return [
            static::online,
        ];
    }
}
