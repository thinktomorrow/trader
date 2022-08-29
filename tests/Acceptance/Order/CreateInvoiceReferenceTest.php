<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class CreateInvoiceReferenceTest extends CartContext
{
    public function test_it_can_create_default_invoice_reference()
    {
        $handler = (new \Thinktomorrow\Trader\Application\Order\Invoice\CreateInvoiceReference((new TestContainer())->get(InvoiceRepository::class)));
        $reference = $handler->create();

        $this->assertEquals(InvoiceReference::fromString(date('y') . date('m') . '0001'), $reference);
    }

    public function test_it_can_create_invoice_reference_following_existing_one()
    {
        $order = $this->createDefaultOrder();
        $order->setInvoiceReference(InvoiceReference::fromString('22080003'));
        $this->orderRepository->save($order);

        $handler = (new \Thinktomorrow\Trader\Application\Order\Invoice\CreateInvoiceReference((new TestContainer())->get(InvoiceRepository::class)));
        $reference = $handler->create();

        $this->assertEquals(InvoiceReference::fromString(date('y') . date('m') . '0004'), $reference);
    }
}
