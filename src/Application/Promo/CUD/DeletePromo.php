<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class DeletePromo
{
    private string $promoId;

    public function __construct(string $promoId)
    {
        $this->promoId = $promoId;
    }

    public function getPromoId(): PromoId
    {
        return PromoId::fromString($this->promoId);
    }
}
