<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\ServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

final class OrderServicePriceResolver
{
    public function __construct(private VatAllocator $vatAllocator) {}

    public function resolveShippingCost(Order $order, ShippingProfile $shippingProfile): ShippingCost
    {
        $tariff = $shippingProfile->findTariffByPrices($order->getSubtotalExcl(), $order->getSubtotalIncl());
        $configuredRate = $tariff?->getVatApplicableRate() ?? VatApplicableAmount::excludingVat(Cash::zero());

        return $this->resolve($order, $configuredRate, ShippingCost::class);
    }

    public function resolvePaymentCost(Order $order, PaymentMethod $paymentMethod): PaymentCost
    {
        return $this->resolve($order, $paymentMethod->getVatApplicableRate(), PaymentCost::class);
    }

    /**
     * @template TServicePrice of ServicePrice
     *
     * @param  class-string<TServicePrice>  $servicePriceClass
     * @return TServicePrice
     */
    private function resolve(Order $order, VatApplicableAmount $amount, string $servicePriceClass): ServicePrice
    {
        return $servicePriceClass::fromVatApplicableAmount(
            $amount,
            $this->vatAllocator->resolveVatApplicableAmountExcludingVat($order, $amount),
        );
    }
}
