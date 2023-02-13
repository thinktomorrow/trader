<?php

namespace Thinktomorrow\Trader\Domain\Model\PaymentMethod;

enum PaymentMethodState: string
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
