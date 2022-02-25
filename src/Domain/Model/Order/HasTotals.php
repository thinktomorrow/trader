<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

trait HasTotals
{
    public function getSubTotal(): SubTotal
    {
        if (count($this->lines) < 1) {
            return SubTotal::zero();
        }

        $price = array_reduce($this->lines, function (?Price $carry, Line $line) {
            return $carry === null
                ? $line->getTotal()
                : $carry->add($line->getTotal());
        }, null);

        return SubTotal::fromPrice($price);
    }

    public function getTotal(): Total
    {
        return Total::fromPrice($this->getSubTotal())
            ->subtract($this->getDiscountTotal())
            ->add($this->getShippingCost())
            ->add($this->getPaymentCost());
    }

    public function getTaxTotal(): Money
    {
        return $this->getTotal()->getIncludingVat()->subtract(
            $this->getTotal()->getExcludingVat()
        );
    }

    public function getDiscountTotal(): DiscountTotal
    {
        if (count($this->discounts) < 1) {
            return DiscountTotal::zero();
        }

        return array_reduce($this->discounts, function (?Price $carry, Discount $discount) {
            return $carry === null
                ? $discount->getTotal()
                : $carry->add($discount->getTotal());
        }, null);
    }

    public function getShippingCost(): ShippingCost
    {
        if (count($this->shippings) < 1) {
            return ShippingCost::zero();
        }

        return array_reduce($this->shippings, function (?Price $carry, Shipping $shipping) {
            return $carry === null
                ? $shipping->getShippingCost()
                : $carry->add($shipping->getShippingCost());
        }, null);
    }

    public function getPaymentCost(): PaymentCost
    {
        return $this->payment ? $this->payment->getPaymentCost() : PaymentCost::zero();
    }
}
