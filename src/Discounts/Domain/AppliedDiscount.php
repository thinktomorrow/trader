<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;

final class AppliedDiscount
{
    /**
     * @var DiscountId
     */
    private $discountId;

    /**
     * @var TypeKey
     */
    private $discountType;

    /**
     * @var Money
     */
    private $amount;

    /**
     * @var Description
     */
    private $description;

    public function __construct(DiscountId $discountId, TypeKey $discountType, Description $description, Money $amount = null)
    {
        $this->discountId = $discountId;
        $this->discountType = $discountType;
        $this->description = $description;
        $this->amount = $amount;
    }

    /**
     * @return DiscountId
     */
    public function discountId(): DiscountId
    {
        return $this->discountId;
    }

    /**
     * unique identifier for applied discount.
     *
     * @return DiscountId
     */
    public function id(): DiscountId
    {
        return $this->discountId();
    }

    /**
     * @return TypeKey
     */
    public function type(): TypeKey
    {
        return $this->discountType;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function amount()
    {
        return $this->amount;
    }
}