<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;

interface PromoRepository
{
    public function save(Promo $promo): void;

    public function find(PromoId $promoId): Promo;

    /** @return list<Promo> */
    public function getAvailableAutomaticPromos(): array;

    public function findAvailableByCouponCode(string $couponCode): ?Promo;

    public function delete(PromoId $promoId): void;

    public function nextReference(): PromoId;

    public function nextPromoDiscountReference(): PromoDiscountId;
}
