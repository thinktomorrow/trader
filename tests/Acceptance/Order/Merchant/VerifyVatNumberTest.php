<?php

declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\VerifyVatNumber;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidator;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumberValidationState;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class VerifyVatNumberTest extends CartContext
{
    public function test_merchant_can_verify_vat_number()
    {
        $order = $this->orderContext->createOrder();
        $billingAddress = $this->orderContext->createBillingAddress();
        $shopper = $this->orderContext->createShopper($order->orderId->get(), 'shopper-aaa', [
            'is_business' => true,
            'data' => json_encode([
                'company' => 'Think Tomorrow',
                'vat_number' => '0123456789',
            ]),
        ]);

        $this->orderContext->addBillingAddressToOrder($order, $billingAddress);
        $this->orderContext->addShopperToOrder($order, $shopper);

        (new TestContainer)->get(VatNumberValidator::class)->setExpectedResult(new VatNumberValidation('BE', '0123456789', VatNumberValidationState::invalid, []));

        $this->orderContext->apps()->merchantOrderApplication()->verifyVatNumber(new VerifyVatNumber(
            $order->orderId->get(),
            '0123456789',
        ));

        $order = $this->orderContext->findOrder($order->orderId);

        $shopper = $order->getShopper();

        $this->assertEquals(false, $shopper->getData('vat_number_valid'));
        $this->assertEquals('invalid', $shopper->getData('vat_number_state'));
        $this->assertEquals('BE', $shopper->getData('vat_number_country'));
    }
}
