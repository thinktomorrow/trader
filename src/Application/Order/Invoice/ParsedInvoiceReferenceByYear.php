<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Invoice;

use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;

class ParsedInvoiceReferenceByYear
{
    public function __construct(public readonly string $year, public readonly string $number)
    {
        $number = (int)$number;

        if ($number < 0) {
            throw new \InvalidArgumentException('Invalid invoice number for invoice reference ' . $this->year . $this->number . '. Invoice number should be a positive integer.');
        }
    }

    public static function fromInvoiceReference(InvoiceReference $invoiceReference): static
    {
        $year = substr($invoiceReference->get(), 0, 2);
        $number = substr($invoiceReference->get(), 2);

        return new self($year, $number);
    }

    public function getNumberAsInt(): int
    {
        return (int) $this->number;
    }
}
