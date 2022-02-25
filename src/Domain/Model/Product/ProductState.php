<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

enum ProductState: string
{
    case draft = 'draft'; // productgroup is offline and in concept
    case online = 'online'; // online
    case archived = 'archived'; // archived and optionally replaced by new product

    public static function onlineStates(): array
    {
        return [
            static::online,
        ];
    }
}
