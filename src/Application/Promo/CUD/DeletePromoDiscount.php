<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class DeletePromoDiscount
{
    public function __construct(private string $promoId, private string $discountId) {}

    public function getPromoId(): PromoId
    {
        return PromoId::fromString($this->promoId);
    }

    public function getDiscountId(): PromoDiscountId
    {
        return PromoDiscountId::fromString($this->discountId);
    }
}
