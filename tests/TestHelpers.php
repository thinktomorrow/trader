<?php

namespace Tests;

use Throwable;
use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Email;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;

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
            'order_state' => OrderState::cart_revived->value,
            'data' => "[]",
        ], [
            Line::class => [
                [
                    'line_id' => 'abc',
                    'variant_id' => 'xxx',
                    'line_price' => 200,
                    'tax_rate' => '10',
                    'includes_vat' => true,
                    'quantity' => 2,
                ],
            ],
            ShippingAddress::class => [
                'country' => 'BE',
                'street' => 'Lierseweg',
                'number' => '81',
                'bus' => null,
                'zipcode' => '2200',
                'city' => 'Herentals',
            ],
            BillingAddress::class => [
                'country' => 'NL',
                'street' => 'example',
                'number' => '12',
                'bus' => 'bus 2',
                'zipcode' => '1000',
                'city' => 'Amsterdam',
            ],
            Discount::class => [
                [
                    'discount_id' => 'ddd',
                    'discount_type' => 'percentage_off',
                    'total' => '10',
                    'tax_rate' => '10',
                    'includes_vat' => true,
                ],
            ],
            Shipping::class => [
                [
                    'shipping_id' => 'sss',
                    'shipping_profile_id' => 'ppp',
                    'shipping_state' => ShippingState::initialized->value,
                    'cost' => '30',
                    'tax_rate' => '10',
                    'includes_vat' => true,
                    'data' => "[]",
                ]
            ],
            Payment::class => [
                'payment_id' => 'ppppp',
                'payment_method_id' => 'mmm',
                'payment_state' => PaymentState::initialized->value,
                'cost' => '20',
                'tax_rate' => '10',
                'includes_vat' => true,
                'data' => "[]",
            ],
            Shopper::class => [
                'shopper_id' => 'abcdef',
                'email' => 'ben@thinktomorrow.be',
                'firstname' => 'Ben',
                'lastname' => 'Cavens',
                'register_after_checkout' => true,
                'customer_id' => null,
            ],
        ]);
    }

    protected function createdCustomer(): Customer
    {
        return Customer::fromMappedData([
            'customer_id' => 'abc',
            'email' => 'ben@thinktomorrow.be',
            'firstname' => 'Ben',
            'lastname' => 'Cavens',
        ]);
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
            'Ben', 'Cavens'
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
        return Product::create(ProductId::fromString('xxx'));
    }

    protected function createdProductWithVariant(): Product
    {
        $product = $this->createdProduct();

        $product->addVariant(Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::fromMoney(
                Money::EUR(10), TaxRate::fromString('20'), false
            ),
            VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
        ));

        return $product;
    }
}
