<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\ItemDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;

final class PercentageOffItemDiscount extends BaseItemDiscount implements Discount, ItemDiscount
{
    /**
     * @var \Thinktomorrow\Trader\Common\Domain\Price\Percentage
     */
    private $percentage;

    /**
     * @var TypeKey
     */
    private $type;

    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
        parent::__construct($id, $conditions, $adjusters);

        $this->percentage = $adjusters['percentage'];
        $this->type = TypeKey::fromDiscount($this);
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function apply(Order $order)
    {
        foreach ($order->items() as $item) {
            if (!$this->applicable($order, $item->id())) {
                // TODO If it could possible apply but one of the conditions isn't yet met, we can keep it
                // as 'AlmostApplicableDiscounts'. This allows us to push incentives to the visitor
                continue;
            }

            $discountAmount = $item->salePrice()->multiply($this->getAffectedItemQuantity($item))
                                   ->multiply($this->percentage->asFloat());

            // Protect against negative overflow where order total would dive under zero
            if ($discountAmount->greaterThan($item->subtotal())) {
                $discountAmount = $item->subtotal();
            }

            // TODO: how to get reasons of nonapplicable? like: you need extra 40,- to benefit from this promo.
            // Like when you enter couponcode and you want the reason for non-acceptance
            $item->addToDiscountTotal($discountAmount);

            // TODO: just add appliedDiscount Here no?

            $item->addDiscount(new AppliedDiscount(
                $this->id,
                $this->type,
                $this->createDescription(),
                $discountAmount
            ));
        }
    }

    private function createDescription()
    {
        return new Description(
            $this->type,
            [
                'percent' => $this->percentage->asPercent(),
            ]
        );
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        parent::validateParameters($conditions, $adjusters);

        if (!isset($adjusters['percentage'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'percentage\', required for discount '.get_class($this));
        }

        if (!$adjusters['percentage'] instanceof Percentage) {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for discount '.get_class($this).'. Instance of '.Percentage::class.' is expected.');
        }
    }
}
