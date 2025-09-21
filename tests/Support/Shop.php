<?php

namespace Tests\Support;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartLinePersonalisation;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerBillingAddress;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderDiscount;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderEvent;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultCartShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerRead;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrder;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderEvent;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class Shop
{
    public static function setUp(): void
    {
        DiscountPriceDefaults::setDiscountTaxRate(VatPercentage::fromString('21'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);

        // States
        (new TestContainer())->add(OrderState::class, DefaultOrderState::class);
        (new TestContainer())->add(ShippingState::class, DefaultShippingState::class);
        (new TestContainer())->add(PaymentState::class, DefaultPaymentState::class);

        // Cart
        (new TestContainer())->add(VariantForCart::class, DefaultVariantForCart::class);
        (new TestContainer())->add(Cart::class, DefaultCart::class);
        (new TestContainer())->add(CartLine::class, DefaultCartLine::class);
        (new TestContainer())->add(CartLinePersonalisation::class, DefaultCartLinePersonalisation::class);
        (new TestContainer())->add(CartShippingAddress::class, DefaultCartShippingAddress::class);
        (new TestContainer())->add(CartBillingAddress::class, DefaultCartBillingAddress::class);
        (new TestContainer())->add(CartShipping::class, DefaultCartShipping::class);
        (new TestContainer())->add(CartPayment::class, DefaultCartPayment::class);
        (new TestContainer())->add(CartShopper::class, DefaultCartShopper::class);
        (new TestContainer())->add(CartDiscount::class, DefaultCartDiscount::class);

        // MerchantOrder
        (new TestContainer())->add(MerchantOrder::class, DefaultMerchantOrder::class);
        (new TestContainer())->add(MerchantOrderLine::class, DefaultMerchantOrderLine::class);
        (new TestContainer())->add(MerchantOrderLinePersonalisation::class, DefaultMerchantOrderLinePersonalisation::class);
        (new TestContainer())->add(MerchantOrderShippingAddress::class, DefaultMerchantOrderShippingAddress::class);
        (new TestContainer())->add(MerchantOrderBillingAddress::class, DefaultMerchantOrderBillingAddress::class);
        (new TestContainer())->add(MerchantOrderShipping::class, DefaultMerchantOrderShipping::class);
        (new TestContainer())->add(MerchantOrderPayment::class, DefaultMerchantOrderPayment::class);
        (new TestContainer())->add(MerchantOrderShopper::class, DefaultMerchantOrderShopper::class);
        (new TestContainer())->add(MerchantOrderDiscount::class, DefaultMerchantOrderDiscount::class);
        (new TestContainer())->add(MerchantOrderEvent::class, DefaultMerchantOrderEvent::class);

        // Customer
        (new TestContainer())->add(CustomerRead::class, DefaultCustomerRead::class);
        (new TestContainer())->add(CustomerShippingAddress::class, DefaultCustomerShippingAddress::class);
        (new TestContainer())->add(CustomerBillingAddress::class, DefaultCustomerBillingAddress::class);

        // Repositories
        (new TestContainer())->add(MysqlOrderRepository::class, new MysqlOrderRepository(new TestContainer(), new TestTraderConfig()));
    }

    public static function tearDown(): void
    {
        TestTraderConfig::clear();
    }
}
