<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscountToOrderException;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\OrderDiscount;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Price\Percentage;

final class PercentageOffDiscount extends BaseDiscount implements Discount, OrderDiscount
{
    /**
     * @var Percentage
     */
    private $percentage;

    /**
     * @var TypeId
     */
    private $type;

    public function __construct(DiscountId $id, array $conditions,  array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->percentage = $adjusters['percentage'];
        $this->adjusters = $adjusters;
        $this->type = TypeId::fromDiscount($this);
    }

    public function apply(Order $order)
    {
        // Check conditions first
        if( ! $this->applicable($order))
        {
            throw new CannotApplyDiscountToOrderException();
        }

        $discountAmount = $order->subtotal()->multiply($this->percentage->asFloat());

        // Protect against negative overflow where order total would dive under zero
        if($discountAmount->greaterThan($order->subtotal())) $discountAmount = $order->subtotal();

        $order->addToDiscountTotal($discountAmount);
        $order->addDiscount(new AppliedDiscount(
            $this->id,
            $this->type,
            $this->createDiscountDescription(),
            $discountAmount
        ));
    }

    private function createDiscountDescription()
    {
        return new DiscountDescription(
            $this->type,
            [
                'percent' => $this->percentage->asPercent()
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