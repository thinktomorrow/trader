<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscount;
use Thinktomorrow\Trader\Discounts\Domain\OrderDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;

final class PercentageOffDiscount extends BaseDiscount implements Discount, OrderDiscount
{
    /**
     * @var Percentage
     */
    private $percentage;

    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
        parent::__construct($id, $conditions, $adjusters);

        $this->percentage = $adjusters['percentage'];
        //$this->type = TypeKey::fromDiscount($this);
    }

    public function apply(Order $order)
    {
        // Check conditions first
        if (!$this->applicable($order)) {
            throw new CannotApplyDiscount();
        }

        $discountAmount = $order->subtotal()->multiply($this->percentage->asFloat());

        // Protect against negative overflow where order total would dive under zero
        if ($discountAmount->greaterThan($order->subtotal())) {
            $discountAmount = $order->subtotal();
        }

        $order->addToDiscountTotal($discountAmount);
        $order->addDiscount(new AppliedDiscount(
            $this->id,
            $this->type,
            $this->createDescription(),
            $discountAmount
        ));
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
