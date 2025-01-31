<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\TaxRateProfile;

enum TaxRateProfileState: string
{
    case offline = 'offline';
    case online = 'online';
    case queued_for_deletion = 'queued_for_deletion'; // pending for permanent deletion

    public static function onlineStates(): array
    {
        return [
            static::online,
        ];
    }
}
