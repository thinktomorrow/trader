<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Invoice;

interface InvoiceRepository
{
    public function lastInvoiceReference(): ?InvoiceReference;

    public function nextInvoiceReference(): InvoiceReference;
}
