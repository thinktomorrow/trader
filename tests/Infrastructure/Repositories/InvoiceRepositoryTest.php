<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class InvoiceRepositoryTest extends TestCase
{
    public function test_it_can_generate_a_next_invoice_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(InvoiceReference::class, $repository->nextInvoiceReference());
        }
    }

    public function test_it_can_get_last_invoice_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $orderContext->createDefaultOrder();

            $repository = $orderContext->repos()->orderRepository();

            $this->assertEquals(InvoiceReference::fromString('order-aaa-invoice-ref'), $repository->lastInvoiceReference());
        }
    }
}
