<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class PaymentMethodRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_method()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $paymentMethod = $orderContext->dontPersist()->createPaymentMethod();

            $repository = $orderContext->repos()->paymentMethodRepository();

            $repository->save($paymentMethod);

            $this->assertEquals($paymentMethod, $repository->find($paymentMethod->paymentMethodId));
        }
    }

    public function test_it_preserves_including_vat_authority(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $paymentMethod = $orderContext->dontPersist()->createPaymentMethod('inclusive-payment', [
                'rate' => '700',
                'tax_mode' => TaxMode::Inclusive->value,
            ]);
            $repository = $orderContext->repos()->paymentMethodRepository();

            $repository->save($paymentMethod);
            $savedPaymentMethod = $repository->find($paymentMethod->paymentMethodId);

            $this->assertEquals('700', $savedPaymentMethod->getRate()->getAmount());
            $this->assertSame(TaxMode::Inclusive, $savedPaymentMethod->getTaxMode());
        }
    }

    public function test_it_can_delete_a_product()
    {
        $methodsNotFound = 0;

        foreach (OrderContext::drivers() as $orderContext) {
            $paymentMethod = $orderContext->createPaymentMethod();

            $repository = $orderContext->repos()->paymentMethodRepository();

            $repository->delete($paymentMethod->paymentMethodId);

            try {
                $repository->find($paymentMethod->paymentMethodId);
            } catch (CouldNotFindPaymentMethod $e) {
                $methodsNotFound++;
            }
        }

        $this->assertCount($methodsNotFound, OrderContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $repository = $orderContext->repos()->paymentMethodRepository();

            $this->assertInstanceOf(PaymentMethodId::class, $repository->nextReference());
        }
    }
}
