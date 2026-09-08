<?php

declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\OrderServicePriceResolver;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;

final class OrderServicePriceResolverTest extends TestCase
{
    private OrderServicePriceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new OrderServicePriceResolver($this->catalogContext->apps()->vatAllocator());
    }

    public function test_it_resolves_the_matching_shipping_tariff_as_a_shipping_cost(): void
    {
        $order = $this->orderContext->createEmptyOrder();
        $shippingProfile = ShippingProfile::create(
            ShippingProfileId::fromString('shipping'),
            ShippingProviderId::fromString('postnl'),
            false,
        );
        $shippingProfile->addTariff(Tariff::create(
            $this->orderContext->repos()->shippingProfileRepository()->nextTariffReference(),
            $shippingProfile->shippingProfileId,
            Money::EUR(100),
            Cash::zero(),
            null,
        ));

        $price = $this->resolver->resolveShippingCost($order, $shippingProfile);

        $this->assertInstanceOf(ShippingCost::class, $price);
        $this->assertEquals(Money::EUR(100), $price->getExcludingVat());
        $this->assertEquals(Money::EUR(100), $price->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Exclusive, $price->getTaxMode());
    }

    public function test_it_resolves_an_including_vat_payment_rate_as_a_payment_cost(): void
    {
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-aaa', [
            'unit_price_excl' => '100',
            'tax_rate' => '21',
        ]));

        $paymentMethod = PaymentMethod::create(
            PaymentMethodId::fromString('payment'),
            PaymentMethodProviderId::fromString('mollie'),
            Money::EUR(121),
            TaxMode::Inclusive,
        );
        $price = $this->resolver->resolvePaymentCost($order, $paymentMethod);

        $this->assertInstanceOf(PaymentCost::class, $price);
        $this->assertEquals(Money::EUR(100), $price->getExcludingVat());
        $this->assertEquals(Money::EUR(121), $price->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Inclusive, $price->getTaxMode());
    }

    public function test_it_resolves_a_missing_shipping_tariff_as_a_zero_shipping_cost(): void
    {
        $order = $this->orderContext->createEmptyOrder();
        $shippingProfile = ShippingProfile::create(
            ShippingProfileId::fromString('shipping'),
            ShippingProviderId::fromString('postnl'),
            false,
        );

        $price = $this->resolver->resolveShippingCost($order, $shippingProfile);

        $this->assertInstanceOf(ShippingCost::class, $price);
        $this->assertEquals(Cash::zero(), $price->getExcludingVat());
        $this->assertSame(TaxMode::Exclusive, $price->getTaxMode());
    }
}
