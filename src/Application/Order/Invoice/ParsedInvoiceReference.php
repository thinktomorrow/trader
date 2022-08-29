<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Invoice;

use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;

class ParsedInvoiceReference
{
    public readonly string $year;
    public readonly string $month;
    public readonly string $number;

    public function __construct(string $year, string $month, string $number)
    {
        $this->year = $year;
        $this->month = $month;
        $this->number = $number;
    }

    public static function fromInvoiceReference(InvoiceReference $invoiceReference): static
    {
        $year = substr($invoiceReference->get(), 0, 2);
        $month = substr($invoiceReference->get(), 2, 2);
        $number = substr($invoiceReference->get(), 4);

        return new static($year, $month, $number);
    }

    public function getNumberAsInt(): int
    {
        return (int) $this->number;
    }
}
