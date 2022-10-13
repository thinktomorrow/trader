<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Invoice;

use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceRepository;

class CreateInvoiceReferenceByYear
{
    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function create(): InvoiceReference
    {
        $lastInvoiceReference = $this->invoiceRepository->lastInvoiceReference();

        if (! $lastInvoiceReference) {
            return InvoiceReference::fromString(date('y'). str_pad((string) 1, 6, "0", STR_PAD_LEFT));
        }

        $parsed = ParsedInvoiceReferenceByYear::fromInvoiceReference($lastInvoiceReference);

        if ($parsed->year != date('y')) {
            return InvoiceReference::fromString(date('y'). str_pad((string) 1, 6, "0", STR_PAD_LEFT));
        }

        return InvoiceReference::fromString(date('y'). str_pad((string) ($parsed->getNumberAsInt() + 1), 6, "0", STR_PAD_LEFT));
    }
}
