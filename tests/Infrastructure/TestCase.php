<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Money\Money;
use Tests\Infrastructure\Common\Catalog;
use Tests\Infrastructure\Common\Shop;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\CountryRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;
use Thinktomorrow\Trader\Infrastructure\Shop\ShopServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use TestHelpers;
    use RefreshDatabase;

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

        Shop::setUp();

        Catalog::setUp();
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
        Catalog::tearDown();

        Shop::tearDown();

        parent::tearDown();
    }

    /**
     * Create the minimum requires domain integrity to avoid mysql db FK failure
     * @return void
     */
    protected function buildWorldForDefaultOrder(): void
    {
        $product = $this->createProductWithPersonalisations();
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
