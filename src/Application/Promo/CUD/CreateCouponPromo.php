<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

class CreateCouponPromo extends CreatePromo
{
    public function __construct(string $couponCode, ?string $startAt, ?string $endAt, bool $isCombinable, array $data)
    {
        parent::__construct($couponCode, $startAt, $endAt, $isCombinable, $data);
    }

    public function getCouponCode(): string
    {
        return $this->couponCode;
    }
}
