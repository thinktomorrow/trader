<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Invoice;

use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceRepository;

class CreateInvoiceReferenceByYearAndMonth
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
            return InvoiceReference::fromString(date('ym'). str_pad((string) 1, 4, "0", STR_PAD_LEFT));
        }

        $parsed = ParsedInvoiceReferenceByYearAndMonth::fromInvoiceReference($lastInvoiceReference);

        if ($parsed->year != date('y') || $parsed->month != date('m')) {
            return InvoiceReference::fromString(date('ym'). str_pad((string) 1, 4, "0", STR_PAD_LEFT));
        }

        return InvoiceReference::fromString(date('ym'). str_pad((string) ($parsed->getNumberAsInt() + 1), 4, "0", STR_PAD_LEFT));
    }
}
