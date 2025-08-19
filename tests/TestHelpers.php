<?php

namespace Tests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Money\Money;
use Thinktomorrow\Trader\Application\Product\CreateProduct;
use Thinktomorrow\Trader\Application\Product\CreateVariant;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;
use Throwable;

trait TestHelpers
{
    protected function disableExceptionHandling(): void
    {
        $this->app->instance(ExceptionHandler::class, new class implements ExceptionHandler {
            public function __construct()
            {
            }

            public function report(\Throwable $e)
            {
            }

            public function render($request, \Throwable $e)
            {
                throw $e;
            }

            public function shouldReport(Throwable $e)
            {
                // TODO: Implement shouldReport() method.
            }

            public function renderForConsole($output, Throwable $e)
            {
                throw $e;
            }
        });
    }

    protected static function createOrder(array $orderValues = [], array $lines = [], array $discounts = [], array $shippings = [], array $payments = [], ?ShippingAddress $shippingAddress = null, ?BillingAddress $billingAddress = null, ?Shopper $shopper = null, array $logEntries = []): Order
    {
        return Order::fromMappedData(array_merge([
            'order_id' => 'xxx',
            'order_ref' => 'xx-ref',
            'invoice_ref' => 'xx-invoice-ref',
            'order_state' => DefaultOrderState::cart_revived,
            'data' => "[]",
        ], $orderValues), [
            Line::class => array_map(fn(Line $line) => [
                ...$line->getMappedData(),
                Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $line->getDiscounts()),
                LinePersonalisation::class => array_map(fn(LinePersonalisation $linePersonalisation) => $linePersonalisation->getMappedData(), $line->getPersonalisations()),
            ], $lines),
            Shipping::class => array_map(fn(Shipping $shipping) => [...array_merge($shipping->getMappedData(), ['shipping_state' => $shipping->getShippingState()]), Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $shipping->getDiscounts())], $shippings),
            Payment::class => array_map(fn(Payment $payment) => [...array_merge($payment->getMappedData(), ['payment_state' => $payment->getPaymentState()]), Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $payment->getDiscounts())], $payments),
            ShippingAddress::class => $shippingAddress?->getMappedData(),
            BillingAddress::class => $billingAddress?->getMappedData(),
            Shopper::class => $shopper?->getMappedData(),
            Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $discounts),
            OrderEvent::class => array_map(fn(OrderEvent $logEntry) => $logEntry->getMappedData(), $logEntries),
        ]);
    }

    protected static function createDefaultOrder(array $orderValues = []): Order
    {
        return static::createOrder(
            $orderValues,
            [static::createLine()],
            [static::createOrderDiscount()],
            [static::createOrderShipping()],
            [static::createOrderPayment()],
            static::createOrderShippingAddress(),
            static::createOrderBillingAddress(),
            static::createOrderShopper(),
            [static::createLogEntry()],
        );
    }

    protected static function createLine(array $values = [], array $aggregateState = [], array $discounts = [], array $personalisations = []): Line
    {
        return Line::fromMappedData(array_merge([
            'line_id' => 'abc',
            'variant_id' => 'yyy',
            'line_price' => 200,
            'tax_rate' => '10',
            'includes_vat' => true,
            'reduced_from_stock' => false,
            'quantity' => 2,
            'data' => json_encode(['product_id' => 'xxx', 'unit_price_including_vat' => '1000', 'unit_price_excluding_vat' => '900', 'foo' => 'bar', 'variant_id' => 'yyy']),
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState), [
            Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $discounts),
            LinePersonalisation::class => array_map(fn(LinePersonalisation $linePersonalisation) => $linePersonalisation->getMappedData(), $personalisations),
        ]);
    }

    protected static function createOrderShipping(array $values = [], array $aggregateState = [], array $discounts = []): Shipping
    {
        return Shipping::fromMappedData(array_merge([
            'order_id' => 'xxx',
            'shipping_id' => 'sss',
            'shipping_profile_id' => 'ppp',
            'shipping_state' => DefaultShippingState::none,
            'cost' => '30',
            'tax_rate' => '10',
            'includes_vat' => true,
            'data' => json_encode(['shipping_profile_id' => 'ppp']),
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState), [
            Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $discounts),
        ]);
    }

    protected static function createOrderPayment(array $values = [], array $aggregateState = [], array $discounts = []): Payment
    {
        return Payment::fromMappedData(array_merge([
            'payment_id' => 'ppppp',
            'payment_method_id' => 'mmm',
            'payment_state' => DefaultPaymentState::initialized,
            'cost' => '20',
            'tax_rate' => '10',
            'includes_vat' => true,
            'data' => json_encode(['payment_method_id' => 'mmm']),
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState), [
            Discount::class => array_map(fn(Discount $discount) => $discount->getMappedData(), $discounts),
        ]);
    }

    protected static function createOrderShippingAddress(array $values = [], array $aggregateState = []): ShippingAddress
    {
        return ShippingAddress::fromMappedData(array_merge([
            'country_id' => 'BE',
            'line_1' => 'Lierseweg 81',
            'line_2' => null,
            'postal_code' => '2200',
            'city' => 'Herentals',
            'data' => "[]",
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState));
    }

    protected static function createOrderBillingAddress(array $values = [], array $aggregateState = []): BillingAddress
    {
        return BillingAddress::fromMappedData(array_merge([
            'country_id' => 'NL',
            'line_1' => 'example 12',
            'line_2' => 'bus 2',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => "[]",
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState));
    }

    protected static function createOrderShopper(array $values = [], array $aggregateState = []): Shopper
    {
        return Shopper::fromMappedData(array_merge([
            'shopper_id' => 'abcdef',
            'email' => 'ben@thinktomorrow.be',
            'is_business' => false,
            'register_after_checkout' => true,
            'customer_id' => 'ccc-123',
            'locale' => 'en_GB',
            'data' => "[]",
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState));
    }

    protected static function createLogEntry(array $values = [], array $aggregateState = []): OrderEvent
    {
        return OrderEvent::fromMappedData(array_merge([
            'entry_id' => 'abc',
            'event' => 'xxx',
            'at' => '2022-02-02 19:19:19',
            'data' => "[]",
        ], $values), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState));
    }

    protected static function createOrderDiscount(array $data = [], array $aggregateState = []): Discount
    {
        return Discount::fromMappedData(array_merge([
            'discount_id' => 'order-discount-abc',
            'discountable_type' => DiscountableType::order->value,
            'discountable_id' => 'xxx',
            'promo_id' => 'abc',
            'promo_discount_id' => 'ddd',
            'total' => '30',
            'tax_rate' => '9',
            'includes_vat' => true,
            'data' => json_encode(['foo' => 'bar', 'promo_id' => ($data['promo_id'] ?? 'abc'), 'promo_discount_id' => ($data['promo_discount_id'] ?? 'ddd')]),
        ], $data), array_merge([
            'order_id' => 'xxx',
        ], $aggregateState));
    }

    protected static function createOrderLineDiscount(array $data = [], array $aggregateState = []): Discount
    {
        return static::createOrderDiscount(array_merge([
            'discount_id' => 'line-abc',
            'discountable_type' => DiscountableType::line->value,
            'discountable_id' => 'abc',
        ], $data));
    }

    protected static function createOrderShippingDiscount(array $data = [], array $aggregateState = []): Discount
    {
        return static::createOrderDiscount(array_merge([
            'discount_id' => 'shipping-uuu',
            'discountable_type' => DiscountableType::shipping->value,
            'discountable_id' => 'sss',
        ], $data));
    }

    protected static function createOrderPaymentDiscount(array $data = [], array $aggregateState = []): Discount
    {
        return static::createOrderDiscount(array_merge([
            'discount_id' => 'shipping-mmm',
            'discountable_type' => DiscountableType::payment->value,
            'discountable_id' => 'ppppp',
        ], $data));
    }

    protected static function createCustomer(): Customer
    {
        return Customer::fromMappedData([
            'customer_id' => 'ccc-123',
            'email' => 'ben@thinktomorrow.be',
            'is_business' => false,
            'locale' => 'fr_BE',
            'data' => json_encode(['foo' => 'bar']),
        ], [
            \Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress::class => null,
            \Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress::class => null,
        ]);
    }

    protected static function createPaymentMethod(array $values = []): PaymentMethod
    {
        return PaymentMethod::fromMappedData(array_merge([
            'payment_method_id' => 'mmm',
            'provider_id' => PaymentMethodProviderId::fromString('mollie')->get(),
            'state' => PaymentMethodState::online->value,
            'rate' => '123',
            'data' => json_encode([]),
        ], $values), [
            CountryId::class => [],
        ]);
    }

    protected static function createShippingProfile(array $values = []): ShippingProfile
    {
        return ShippingProfile::fromMappedData(array_merge([
            'shipping_profile_id' => 'ppp',
            'provider_id' => 'postnl',
            'state' => ShippingProfileState::online->value,
            'requires_address' => true,
            'data' => json_encode([]),
        ], $values), [
            Tariff::class => [],
            CountryId::class => [],
        ]);
    }

    protected static function createVatRateWithoutBaseRates(array $values = []): VatRate
    {
        return static::createVatRate($values, [], false);
    }

    protected static function createVatRate(array $values = [], array $baseRateValues = [], bool $withBaseRates = true): VatRate
    {
        return VatRate::fromMappedData(array_merge([
            'vat_rate_id' => 'vatRate-' . Str::random(10),
            'country_id' => 'BE',
            'rate' => '21',
            'is_standard' => false,
            'state' => VatRateState::online->value,
            'data' => json_encode([]),
        ], $values), [
            BaseRate::class => $withBaseRates ? [
                array_merge([
                    'base_rate_id' => 'baseRate-' . Str::random(10),
                    'origin_vat_rate_id' => 'originVatRate-123',
                    'target_vat_rate_id' => 'ppp',
                    'rate' => '10',
                ], $baseRateValues),
            ] : [],
        ]);
    }

    protected static function createCountry(array $values = []): Country
    {
        return Country::fromMappedData(array_merge([
            'country_id' => 'BE',
            'data' => json_encode([]),
        ], $values));
    }

    protected static function createCustomerLogin(): CustomerLogin
    {
        return CustomerLogin::fromMappedData([
            'customer_id' => 'ccc-123',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ]);
    }

    protected static function createACustomerLogin(): Customer
    {
        $customer = Customer::create(
            $customerId = CustomerId::fromString('azerty'),
            Email::fromString('ben@thinktomorrow.be'),
            true,
            Locale::fromString('fr-be'),
        );

        app(CustomerRepository::class)->save($customer);

        $customerLogin = CustomerLogin::create(
            $customerId,
            Email::fromString('ben@thinktomorrow.be'),
            bcrypt('123456')
        );

        app(CustomerLoginRepository::class)->save($customerLogin);

        return $customer;
    }

    protected static function createProduct(): Product
    {
        $product = Product::create(ProductId::fromString('xxx'));
        $product->updateState(ProductState::online);

        return $product;
    }

    protected static function createOfflineProduct(): Product
    {
        $product = Product::create(ProductId::fromString('xxx'));
        $product->updateState(ProductState::offline);

        return $product;
    }

    protected static function createProductWithPersonalisations(): Product
    {
        $product = static::createProductWithVariant();

        $product->updatePersonalisations([
            Personalisation::create(
                $product->productId,
                PersonalisationId::fromString('ooo'),
                PersonalisationType::fromString(PersonalisationType::TEXT),
                ['foo' => 'bar']
            ),
        ]);

        return $product;
    }

    protected static function createProductWithProductVariantProperties(): Product
    {
        $product = static::createProduct();

        $product->updateProductTaxa([
            ProductTaxon::create($product->productId, TaxonomyId::fromString('ooo'), TaxonomyType::variant_property, TaxonId::fromString('xxx')),
            ProductTaxon::create($product->productId, TaxonomyId::fromString('ooo'), TaxonomyType::variant_property, TaxonId::fromString('yyy')),
            ProductTaxon::create($product->productId, TaxonomyId::fromString('ppp'), TaxonomyType::variant_property, TaxonId::fromString('zzz')),
        ]);

        $variant = static::createVariantWithVariantProperty();

        $variant->updateVariantTaxa([
            VariantTaxon::create($variant->variantId, TaxonomyId::fromString('ooo'), TaxonomyType::variant_property, TaxonId::fromString('xxx')),
            VariantTaxon::create($variant->variantId, TaxonomyId::fromString('ppp'), TaxonomyType::variant_property, TaxonId::fromString('zzz')),
        ]);

        $product->createVariant($variant);

        return $product;
    }

    protected static function createTaxonomiesAndTaxa(): array
    {
        $taxonomyIds = ['ooo', 'ppp', 'qqq'];
        $taxonIds = ['xxx', 'yyy', 'zzz'];

        $taxonomies = [];
        $taxa = [];

        foreach ($taxonomyIds as $taxonomyId) {
            $taxonomies[] = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create(TaxonomyId::fromString($taxonomyId), TaxonomyType::variant_property);

            foreach ($taxonIds as $taxonId) {
                $taxa[] = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create(TaxonId::fromString($taxonId), TaxonomyId::fromString($taxonomyId));
            }
        }

        return [
            $taxonomies,
            $taxa,
        ];
    }

    protected static function createProductWithVariant(): Product
    {
        $product = static::createProduct();

        return static::withVariant($product, false);
    }

    protected static function createProductWithVariantAndTaxon(): Product
    {
        $product = static::createProduct();

        return static::withVariant($product, true);
    }

    protected static function createOfflineProductWithVariant(): Product
    {
        $product = static::createOfflineProduct();

        return static::withVariant($product, false);
    }

    private static function withVariant(Product $product, bool $withDefaultTaxon = true): Product
    {
        if ($withDefaultTaxon) {
            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, TaxonomyId::fromString('ooo'), TaxonomyType::variant_property, TaxonId::fromString('xxx')),
            ]);

            $variant = static::createVariantWithVariantProperty();
        } else {
            $variant = static::createVariant();
        }

        $product->createVariant($variant);

        return $product;
    }

    protected static function createVariantWithVariantProperty(): Variant
    {
        return static::createVariant(true);
    }

    protected static function createVariant(bool $withVariantProperty = false): Variant
    {
        $variant = Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::fromMoney(
                Money::EUR(10),
                VatPercentage::fromString('20'),
                false
            ),
            VariantSalePrice::fromMoney(Money::EUR(8), VatPercentage::fromString('20'), false),
            'fake-sku',
        );

        if ($withVariantProperty) {
            $variant->updateVariantTaxa([
                VariantTaxon::create(
                    $variant->variantId,
                    TaxonomyId::fromString('ooo'),
                    TaxonomyType::variant_property,
                    TaxonId::fromString('xxx')
                ),
            ]);
        }

        return $variant;
    }

    protected static function createStockItem(): Product
    {
        $product = static::createProduct();
        $product->updateOptions([Option::create($product->productId, OptionId::fromString('ooo'), ['foo' => 'bar'])]);
        $product->updateOptionValues(OptionId::fromString('ooo'), [
            OptionValue::create(OptionId::fromString('ooo'), OptionValueId::fromString('ppp'), ['foo' => 'bar']),
        ]);
        $variant = static::createVariantWithVariantProperty();

        $product->createVariant($variant);

        return $product;
    }

    protected static function createCatalog(TaxonApplication $taxonApplication, ProductApplication $productApplication, ProductRepository $productRepository)
    {
        $taxonId = $taxonApplication->createTaxon(new CreateTaxon('brand', 'foobar', 'nl', ['title' => ['nl' => 'foobar nl']]));
        $taxonChildId = $taxonApplication->createTaxon(new CreateTaxon('brand', 'foobar-child', 'nl', ['title' => ['nl' => 'foobar child nl']], $taxonId->get()));

        $productId = $productApplication->createProduct(new CreateProduct([$taxonId->get()], "100", "6", 'sku', ['title' => ['nl' => 'product one']], ['title' => ['nl' => 'variant title one']]));
        $product2Id = $productApplication->createProduct(new CreateProduct([$taxonChildId->get()], "250", "12", 'sku-2', ['title' => ['nl' => 'product two']], ['title' => ['nl' => 'variant title two']]));
        $product3Id = $productApplication->createProduct(new CreateProduct([], "500", "21", 'sku-3', ['title' => ['nl' => 'product three']], ['title' => ['nl' => 'variant title three']]));

        // Force order for consistent testing assertions
        DB::table('trader_products')->where('product_id', $productId->get())->update(['order_column' => 0]);
        DB::table('trader_products')->where('product_id', $product2Id->get())->update(['order_column' => 1]);
        DB::table('trader_products')->where('product_id', $product3Id->get())->update(['order_column' => 2]);

        // Set every product online
        foreach ([$productId, $product2Id, $product3Id] as $prodId) {
            $product = $productRepository->find($prodId);
            $product->updateState(ProductState::online);

            $productRepository->save($product);
        }

        $productApplication->createVariant(new CreateVariant($productId->get(), "120", "6", 'sku-4', ['title' => ['nl' => 'product one - variant two']], []));
    }

    protected static function createPromo(array $mappedData = [], array $discounts = []): Promo
    {
        return Promo::fromMappedData(array_merge([
            'promo_id' => 'abc',
            'state' => PromoState::online->value,
            'is_combinable' => false,
            'coupon_code' => null,
            'start_at' => null,
            'end_at' => null,
            'data' => json_encode([]),
        ], $mappedData), [\Thinktomorrow\Trader\Domain\Model\Promo\Discount::class => $discounts]);
    }

    protected static function createDiscount(array $mappedData = [], array $conditions = [])
    {
        return FixedAmountDiscount::fromMappedData(array_merge([
            'discount_id' => 'ddd',
            'data' => json_encode(['amount' => '40']),
        ], $mappedData), [
            'promo_id' => 'abc',
        ], [
            Condition::class => $conditions,
        ]);
    }

    protected static function createCondition(array $mappedData = [])
    {
        return MinimumLinesQuantity::fromMappedData(array_merge([
            'data' => json_encode(['minimum_quantity' => 5]),
        ], $mappedData), []);
    }


    protected function assertArrayEqualsWithWildcard(array $expected, array $actual, $message = null): void
    {
        $message = $message ?: 'actual array: ' . print_r($actual, true) . ' does not match expected: ' . print_r($expected, true);

        $this->assertEquals(count($expected), count($actual), 'Count doesn\'t match: ' . $message);
        $this->assertEquals(array_keys($expected), array_keys($actual), 'Keys do not match: ' . $message);

        foreach ($expected as $expectedKey => $expectedValue) {
            if (is_array($expectedValue) && !is_array($actual[$expectedKey])) {
                $this->assertEquals($expectedValue, $actual[$expectedKey], $message);
            }

            if (is_array($expectedValue)) {
                $this->assertArrayEqualsWithWildcard($expectedValue, $actual[$expectedKey], $message);

                continue;
            }

            if ($expectedValue == '*') {
                $this->assertNotNull($actual[$expectedKey]);

                continue;
            }

            $this->assertEquals($expectedValue, $actual[$expectedKey], $message);
        }
    }
}
