<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Helpers\HandlesArrayDotSyntax;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;

final class AppliedDiscount
{
    use HandlesArrayDotSyntax;

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
    private $discountAmount;

    /**
     * @var Money
     */
    private $discountBasePrice;

    /**
     * @var Percentage
     */
    private $discountPercentage;

    /**
     * @var array
     */
    private $data;

    public function __construct(DiscountId $discountId, string $discountType, Money $discountAmount = null, Money $discountBasePrice = null, Percentage $discountPercentage, array $data = [])
    {
        $this->discountId = $discountId;
        $this->discountType = $discountType;
        $this->discountAmount = $discountAmount;
        $this->discountBasePrice = $discountBasePrice;
        $this->discountPercentage = $discountPercentage;
        $this->data = $data;
    }

    /**
     * @return DiscountId
     */
    public function discountId(): DiscountId
    {
        return $this->discountId;
    }

    /**
     * @return string
     */
    public function discountType(): string
    {
        return $this->discountType;
    }

    /**
     * Gives the value of the discount in terms of money. Not in all cases the discount is
     * a discount of the price, it can also be a free shipment, free item and such. To
     * determine the highest discount in these cases, we need to be able to translate
     * these discounts to their 'amount' impact so we can compare them.
     *
     * @return Money
     */
    public function discountAmount(): Money
    {
        return $this->discountAmount;
    }

    public function discountBasePrice(): Money
    {
        return $this->discountBasePrice;
    }

    public function discountPercentage(): Percentage
    {
        return $this->discountPercentage;
    }

    public function data($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->data;
        }

        if (!is_null($key) && isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $this->handlesArrayDotSyntax($key, $default);
    }
}
