<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

interface Discount extends ChildAggregate, Mappable
{
    public function getPromoId(): PromoId;

    public function getDiscountId(): PromoDiscountId;

    public function getDiscountableType(): DiscountableType;

    /** @return list<Condition> */
    public function getConditions(): array;
}
