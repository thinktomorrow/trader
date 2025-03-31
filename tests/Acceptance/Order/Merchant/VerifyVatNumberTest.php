<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateShopper;
use Thinktomorrow\Trader\Application\Order\Merchant\VerifyVatNumber;
use Thinktomorrow\Trader\Application\VatRate\VatNumberValidation;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\ShopperUpdatedByMerchant;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumberValidationState;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

class VerifyVatNumberTest extends CartContext
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();
    }

    public function test_merchant_can_verify_vat_number()
    {
        $order = $this->createOrder(
            ['order_id' => 'xxx'],
            [],
            [],
            [],
            [],
            null,
            $this->createOrderBillingAddress([
                'country_id' => 'BE',
                'line_1' => 'line-1',
                'line_2' => 'line-2',
                'postal_code' => 'postal-code',
                'city' => 'city',
            ]),
            $this->createOrderShopper([
                'email' => 'ben@thinktomorrow.be',
                'is_business' => true,
                'locale' => 'en_GB',
                'data' => json_encode([
                    'foo' => 'bar',
                    'vat_number' => '0123456789',
                ]),
            ])
        );

        $this->orderRepository->save($order);

        $this->vatNumberValidator->setExpectedResult(new VatNumberValidation('BE', '0123456789', VatNumberValidationState::invalid, []));

        $this->merchantOrderApplication->verifyVatNumber(new VerifyVatNumber(
            $order->orderId->get(),
            '0123456789',
        ));

        $order = $this->orderRepository->find($order->orderId);

        $shopper = $order->getShopper();

        $this->assertEquals(false, $shopper->getData('vat_number_valid'));
        $this->assertEquals('invalid', $shopper->getData('vat_number_state'));
        $this->assertEquals('BE', $shopper->getData('vat_number_country'));
    }
}
