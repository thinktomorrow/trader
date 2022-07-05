<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

interface PromoRepository
{
    public function save(Promo $promo): void;

    public function find(PromoId $promoId): Promo;

    public function delete(PromoId $promoId): void;

    public function nextReference(): PromoId;

    public function nextDiscountReference(): DiscountId;
}
