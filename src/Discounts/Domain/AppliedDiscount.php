<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeId;

class AppliedDiscount
{
    /**
     * @var DiscountId
     */
    private $discountId;

    /**
     * @var TypeId
     */
    private $discountType;

    /**
     * @var Money
     */
    private $amount;

    /**
     * @var DiscountDescription
     */
    private $description;

    public function __construct(DiscountId $discountId, TypeId $discountType, DiscountDescription $description, Money $amount = null)
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
     * @return TypeId
     */
    public function discountType(): TypeId
    {
        return $this->discountType;
    }

    public function amount()
    {
        return $this->amount;
    }

    /**
     * @return DiscountDescription
     */
    public function description(): DiscountDescription
    {
        return $this->description;
    }
}