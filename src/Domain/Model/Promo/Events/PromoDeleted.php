<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Events;

use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

final class PromoDeleted
{
    public function __construct(public readonly PromoId $promoId)
    {
    }
}
