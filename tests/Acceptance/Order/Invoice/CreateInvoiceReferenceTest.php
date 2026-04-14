<?php

declare(strict_types=1);

namespace Tests\Acceptance\Order\Invoice;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Invoice\CreateInvoiceReferenceByYearAndMonth;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;

class CreateInvoiceReferenceTest extends CartContext
{
    public function test_it_can_create_default_invoice_reference()
    {
        $handler = (new CreateInvoiceReferenceByYearAndMonth(
            $this->orderContext->repos()->invoiceRepository()
        ));
        $reference = $handler->create();

        $this->assertEquals(InvoiceReference::fromString(date('y').date('m').'0001'), $reference);
    }

    public function test_it_can_create_invoice_reference_following_existing_one()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->setInvoiceReference(InvoiceReference::fromString(date('y').date('m').'0003'));
        $this->orderContext->repos()->orderRepository()->save($order);

        $handler = (new CreateInvoiceReferenceByYearAndMonth(
            $this->orderContext->repos()->invoiceRepository()
        ));
        $reference = $handler->create();

        $this->assertEquals(InvoiceReference::fromString(date('y').date('m').'0004'), $reference);
    }

    public function test_it_can_create_invoice_reference_following_existing_one_with_new_date()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order->setInvoiceReference(InvoiceReference::fromString('22080003'));
        $this->orderContext->repos()->orderRepository()->save($order);

        $handler = (new CreateInvoiceReferenceByYearAndMonth(
            $this->orderContext->repos()->invoiceRepository()
        ));
        $reference = $handler->create();

        $this->assertEquals(InvoiceReference::fromString(date('y').date('m').'0001'), $reference);
    }
}
