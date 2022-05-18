<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

enum ProductState: string
{
    case offline = 'offline'; // product is offline
    case online = 'online'; // online
    case archived = 'archived'; // archived and no longer in use
    case queued_for_deletion = 'queued_for_deletion'; // pending for permanent deletion

    public static function onlineStates(): array
    {
        return [
            static::online,
        ];
    }
}
