<?php

namespace Tests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Money\Money;
use Thinktomorrow\Trader\Application\Product\CreateProduct;
use Thinktomorrow\Trader\Application\Product\CreateVariant;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Throwable;

trait TestHelpers
{
    protected function disableExceptionHandling()
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

    protected function createdOrder(): Order
    {
        return Order::fromMappedData([
            'order_id' => 'xxx',
            'order_ref' => 'xx-ref',
            'order_state' => OrderState::cart_revived->value,
            'data' => "[]",
        ], [
            Line::class => [
                [
                    'order_id' => 'xxx',
                    'line_id' => 'abc',
                    'variant_id' => 'yyy',
                    'line_price' => 200,
                    'tax_rate' => '10',
                    'includes_vat' => true,
                    'quantity' => 2,
                    'data' => json_encode(['product_id' => 'aab', 'unit_price' => '1000', 'foo' => 'bar']),
                    Discount::class => [$this->createOrderDiscount(OrderId::fromString('xxx'), ['discount_id' => 'line-abc', 'discountable_type' => DiscountableType::line->value, 'discountable_id' => 'abc'])->getMappedData()],
                ],
            ],
            ShippingAddress::class => [
                'country_id' => 'BE',
                'line_1' => 'Lierseweg 81',
                'line_2' => null,
                'postal_code' => '2200',
                'city' => 'Herentals',
                'data' => "[]",
            ],
            BillingAddress::class => [
                'country_id' => 'NL',
                'line_1' => 'example 12',
                'line_2' => 'bus 2',
                'postal_code' => '1000',
                'city' => 'Amsterdam',
                'data' => "[]",
            ],
            Discount::class => [
                $this->createOrderDiscount(OrderId::fromString('xxx'))->getMappedData(),
            ],
            Shipping::class => [
                [
                    'order_id' => 'xxx',
                    'shipping_id' => 'sss',
                    'shipping_profile_id' => 'ppp',
                    'shipping_state' => ShippingState::initialized->value,
                    'cost' => '30',
                    'tax_rate' => '10',
                    'includes_vat' => true,
                    'data' => "[]",
                    Discount::class => [$this->createOrderDiscount(OrderId::fromString('xxx'), ['discount_id' => 'shipping-uuu', 'discountable_type' => DiscountableType::shipping->value, 'discountable_id' => 'sss'])->getMappedData()],
                ],
            ],
            Payment::class => [
                'payment_id' => 'ppppp',
                'payment_method_id' => 'mmm',
                'payment_state' => PaymentState::initialized->value,
                'cost' => '20',
                'tax_rate' => '10',
                'includes_vat' => true,
                'data' => "[]",
                Discount::class => [],
            ],
            Shopper::class => [
                'shopper_id' => 'abcdef',
                'email' => 'ben@thinktomorrow.be',
                'is_business' => false,
                'register_after_checkout' => true,
                'customer_id' => null,
                'locale' => 'en_GB',
                'data' => "[]",
            ],
        ]);
    }

    protected function createOrderDiscount(OrderId $orderId, array $data = []): Discount
    {
        return Discount::fromMappedData(array_merge([
            'discount_id' => 'ababab',
            'discountable_type' => DiscountableType::order->value,
            'discountable_id' => $orderId->get(),
            'promo_id' => 'def',
            'promo_discount_id' => 'abc',
            'total' => '30',
            'tax_rate' => '9',
            'includes_vat' => true,
            'data' => json_encode(['foo' => 'bar']),
        ], $data), [
            'order_id' => $orderId->get(),
        ]);
    }

    protected function createdCustomer(): Customer
    {
        return Customer::fromMappedData([
            'customer_id' => 'abc',
            'email' => 'ben@thinktomorrow.be',
            'is_business' => false,
            'locale' => 'fr_BE',
            'data' => json_encode(['foo' => 'bar']),
        ]);
    }

    protected function createPaymentMethod(array $values = []): PaymentMethod
    {
        return PaymentMethod::fromMappedData(array_merge([
            'payment_method_id' => 'ppp',
            'rate' => '123',
            'data' => json_encode([]),
        ], $values));
    }

    protected function createShippingProfile(array $values = []): ShippingProfile
    {
        return ShippingProfile::fromMappedData(array_merge([
            'shipping_profile_id' => 'sss',
            'state' => ShippingProfileState::online->value,
            'data' => json_encode([]),
        ], $values), [
            Tariff::class => [],
            CountryId::class => [],
        ]);
    }

    protected function createCountry(array $values = []): Country
    {
        return Country::fromMappedData(array_merge([
            'country_id' => 'BE',
            'data' => json_encode([]),
        ], $values));
    }

    protected function createdCustomerLogin(): CustomerLogin
    {
        return CustomerLogin::fromMappedData([
            'customer_id' => 'abc',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ]);
    }

    protected function createACustomerLogin(): Customer
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

    protected function createdProduct(): Product
    {
        $product = Product::create(ProductId::fromString('xxx'));
        $product->updateState(ProductState::online);

        return $product;
    }

    protected function createdProductWithOption(): Product
    {
        $product = $this->createdProduct();

        $product->updateOptions([
            $option = Option::create($product->productId, OptionId::fromString('ooo'), ['foo' => 'bar']),
        ]);

        $option->updateOptionValues([OptionValue::create($option->optionId, OptionValueId::fromString('xxx'), [
            'label' => [
                'nl' => 'option label nl',
                'en' => 'option label en',
            ],
            'value' => [
                'nl' => 'option value nl',
                'en' => 'option value en',
            ],
        ])]);

        $variant = $this->createdVariantWithOption();
        $product->createVariant($variant);

        return $product;
    }

    protected function createdProductWithOptions(): Product
    {
        $product = $this->createdProduct();

        $product->updateOptions([
            $option = Option::create($product->productId, OptionId::fromString('ooo'), ['foo' => 'bar']),
            $option2 = Option::create($product->productId, OptionId::fromString('ppp'), ['foo' => 'baz']),
        ]);

        $option->updateOptionValues([
            OptionValue::create($option->optionId, OptionValueId::fromString('xxx'), [
                'label' => [
                    'nl' => 'option label nl 1',
                    'en' => 'option label en 1',
                ],
                'value' => [
                    'nl' => 'option value nl 1',
                    'en' => 'option value en 1',
                ],
            ]),
            OptionValue::create($option->optionId, OptionValueId::fromString('yyy'), [
                'label' => [
                    'nl' => 'option label nl 2',
                    'en' => 'option label en 2',
                ],
                'value' => [
                    'nl' => 'option value nl 2',
                    'en' => 'option value en 2',
                ],
            ]),
        ]);

        $option2->updateOptionValues([
            OptionValue::create($option2->optionId, OptionValueId::fromString('zzz'), [
                'label' => [
                    'nl' => 'option label nl 3',
                    'en' => 'option label en 3',
                ],
                'value' => [
                    'nl' => 'option value nl 3',
                    'en' => 'option value en 3',
                ],
            ]),
        ]);

        $variant = $this->createdVariantWithOption();

        // TODO: how/where to protect from duplicates (multiple values from same option). In create/updateVariant of product
        $variant->updateOptionValueIds([
            OptionValueId::fromString('xxx'),
            OptionValueId::fromString('zzz'),
        ]);
        $product->createVariant($variant);

        return $product;
    }

    protected function createdProductWithVariant(): Product
    {
        $product = $this->createdProduct();
        $variant = $this->createdVariantWithOption();

        $product->createVariant($variant);

        return $product;
    }

//    protected function createdVariant(): Variant
//    {
//        $variant = Variant::create(
//            ProductId::fromString('xxx'),
//            VariantId::fromString('yyy'),
//            VariantUnitPrice::fromMoney(
//                Money::EUR(10),
//                TaxRate::fromString('20'),
//                false
//            ),
//            VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
//        );
//
//        return $variant;
//    }

    protected function createdVariantWithOption(): Variant
    {
        $variant = Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::fromMoney(
                Money::EUR(10),
                TaxRate::fromString('20'),
                false
            ),
            VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
        );

        $variant->updateOptionValueIds([
            OptionValueId::fromString('option-value-id'),
        ]);

        return $variant;
    }

    protected function createCatalog(TaxonApplication $taxonApplication, ProductApplication $productApplication, ProductRepository $productRepository)
    {
        $taxonId = $taxonApplication->createTaxon(new CreateTaxon('foobar', ['title' => ['nl' => 'foobar nl']]));
        $taxonChildId = $taxonApplication->createTaxon(new CreateTaxon('foobar-child', ['title' => ['nl' => 'foobar child nl']], $taxonId->get()));

        $productId = $productApplication->createProduct(new CreateProduct([$taxonId->get()], "100", "6", ['title' => ['nl' => 'product one']]));
        $product2Id = $productApplication->createProduct(new CreateProduct([$taxonChildId->get()], "250", "12", ['title' => ['nl' => 'product two']]));
        $product3Id = $productApplication->createProduct(new CreateProduct([], "500", "21", ['title' => ['nl' => 'product three']]));

        // Set every product online
        foreach ([$productId, $product2Id, $product3Id] as $prodId) {
            $product = $productRepository->find($prodId);
            $product->updateState(ProductState::online);

            $productRepository->save($product);
        }

        $productApplication->createVariant(new CreateVariant($productId->get(), "120", "6", ['title' => ['nl' => 'product one - variant two']]));
    }

    protected function createPromo(array $mappedData = [], array $discounts = []): Promo
    {
        return Promo::fromMappedData(array_merge([
            'promo_id' => 'xxx',
            'state' => PromoState::online->value,
            'is_combinable' => false,
            'coupon_code' => null,
            'start_at' => null,
            'end_at' => null,
            'data' => json_encode([]),
        ], $mappedData), [\Thinktomorrow\Trader\Domain\Model\Promo\Discount::class => $discounts]);
    }

    protected function createDiscount(array $mappedData = [], array $conditions = [])
    {
        return FixedAmountDiscount::fromMappedData(array_merge([
            'discount_id' => 'abc',
            'data' => json_encode(['amount' => '40']),
        ], $mappedData), [
            'promo_id' => 'xxx',
        ], [
            Condition::class => $conditions,
        ]);
    }

    protected function createCondition(array $mappedData = [])
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
            if (is_array($expectedValue) && ! is_array($actual[$expectedKey])) {
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
