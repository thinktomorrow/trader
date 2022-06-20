<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;

trait HasTotals
{
    public function getSubTotal(): Total
    {
        $total = Total::zero();

        if (count($this->lines) < 1) {
            return $total;
        }

        return array_reduce($this->lines, function (?PriceTotal $carry, Line $line) {
            return $carry === null
                ? $line->getTotal()
                : $carry->add($line->getTotal());
        }, $total);
    }

    public function getTotal(): Total
    {
        return $this->getSubTotal()
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

        $discountMoney = array_reduce($this->discounts, function (?Money $carry, Discount $discount) {
            return $carry === null
                ? $discount->getTotal()
                : $carry->add($discount->getTotal());
        }, null);

        return DiscountTotal::fromDefault($discountMoney);
    }

    public function getShippingCost(): ShippingCost
    {
        if (count($this->shippings) < 1) {
            return ShippingCost::zero();
        }

        return array_reduce($this->shippings, function (?PriceTotal $carry, Shipping $shipping) {
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
