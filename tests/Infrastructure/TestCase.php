<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Money\Money;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Country\CountryRepository;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Application\Cart\Read\CartLinePersonalisation;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerBillingAddress;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderDiscount;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLogEntry;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;
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
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrder;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderLogEntry;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;

    protected function getEnvironmentSetUp($app)
    {
        # Setup default database to use sqlite :memory:
//        $app['config']->set('database.default', 'mysql');
//        $app['config']->set('database.connections.mysql', [
//            'driver' => 'mysql',
//            'host' => '127.0.0.1',
//            'port' => '3306',
//            'database' => 'trader-test',
//            'username' => 'root',
//            'password' => null,
//            'prefix' => '',
//        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        DiscountPriceDefaults::setDiscountTaxRate(TaxRate::fromString('21'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);

        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
        (new TestContainer())->add(VariantForCart::class, DefaultVariantForCart::class);

        // Cart
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
        (new TestContainer())->add(MerchantOrderLogEntry::class, DefaultMerchantOrderLogEntry::class);

        // Customer
        (new TestContainer())->add(CustomerRead::class, DefaultCustomerRead::class);
        (new TestContainer())->add(CustomerShippingAddress::class, DefaultCustomerShippingAddress::class);
        (new TestContainer())->add(CustomerBillingAddress::class, DefaultCustomerBillingAddress::class);
    }

    public function getPackageProviders($app)
    {
        return [
            TraderServiceProvider::class,
            ShopServiceProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        InMemoryOrderRepository::clear();
        InMemoryProductRepository::clear();
        InMemoryVariantRepository::clear();
        InMemoryTaxonRepository::clear();
        InMemoryPromoRepository::clear();

        parent::tearDown();
    }

    /**
     * Create the minimum requires domain integrity to avoid mysql db FK failure
     * @return void
     */
    protected function buildWorldForDefaultOrder(): void
    {
        $product = $this->createdProductWithPersonalisations();
        app(ProductRepository::class)->save($product);

        $promo = $this->createPromo([], [
            $this->createDiscount(),
        ]);
        app(PromoRepository::class)->save($promo);

        $shippingProfile = ShippingProfile::create(ShippingProfileId::fromString('ppp'), true);
        app(ShippingProfileRepository::class)->save($shippingProfile);

        $paymentMethod = PaymentMethod::create(PaymentMethodId::fromString('mmm'), Money::EUR(10));
        app(PaymentMethodRepository::class)->save($paymentMethod);

        $country = Country::create(CountryId::fromString('BE'), []);
        app(CountryRepository::class)->save($country);

        $country = Country::create(CountryId::fromString('NL'), []);
        app(CountryRepository::class)->save($country);

        $customer = Customer::create(CustomerId::fromString('ccc-123'), Email::fromString('ben@thinktomorrow.be'), false, Locale::fromString('nl'));
        app(CustomerRepository::class)->save($customer);
    }
}
