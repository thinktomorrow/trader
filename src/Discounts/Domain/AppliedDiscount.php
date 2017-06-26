<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Order\Domain\ItemCollection;

class AppliedDiscount
{
    /**
     * @var DiscountId
     */
    private $discountId;

    /**
     * @var DiscountType
     */
    private $discountType;

    /**
     * @var DiscountDescription
     */
    private $description;

    /**
     * @var ItemCollection
     */
    private $affectedItems;

    public function __construct(DiscountId $discountId, DiscountType $discountType, DiscountDescription $description, ItemCollection $affectedItems)
    {
        $this->discountId = $discountId;
        $this->discountType = $discountType;
        $this->description = $description;
        $this->affectedItems = $affectedItems;
    }

    /**
     * @return DiscountId
     */
    public function discountId(): DiscountId
    {
        return $this->discountId;
    }

    /**
     * @return DiscountType
     */
    public function discountType(): DiscountType
    {
        return $this->discountType;
    }

    /**
     * @return DiscountDescription
     */
    public function description(): DiscountDescription
    {
        return $this->description;
    }

    /**
     * @return ItemCollection
     */
    public function affectedItems(): ItemCollection
    {
        return $this->affectedItems;
    }

    /**
     * Is this applied discount affecting certain / all
     * items or on the order level in general.
     *
     * @return bool
     */
    public function affectsItems(): bool
    {
        return !$this->affectedItems()->isEmpty();
    }

}