<?php

namespace Tests\Acceptance\Order\Invoice;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Order\Invoice\ParsedInvoiceReferenceByYear;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;

class ParsedInvoiceTest extends TestCase
{
    public function test_it_can_parse_invoice_ref()
    {
        $ref = InvoiceReference::fromString(23000003);
        $parsed = ParsedInvoiceReferenceByYear::fromInvoiceReference($ref);

        $this->assertEquals("23", $parsed->year);
        $this->assertEquals("000003", $parsed->number);
        $this->assertEquals(3, $parsed->getNumberAsInt());

        //        $parsed

        //
        // 23000003
    }
}
