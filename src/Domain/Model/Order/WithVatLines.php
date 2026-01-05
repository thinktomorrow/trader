<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

trait WithVatLines
{
    /** @var VatAllocatedLine[] */
    private array $vatLines = [];

    protected function initializeVatLinesFromState(array $state): void
    {
        $this->vatLines = array_map(fn($vatLineData) => new VatAllocatedLine(
            Money::EUR($vatLineData['taxable_base']),
            Money::EUR($vatLineData['vat_amount']),
            VatPercentage::fromString($vatLineData['vat_percentage']),
        ), json_decode($state['vat_lines'], true));
    }

    protected function getVatLinesState(): array
    {
        return [
            'vat_lines' => json_encode(array_map(fn(VatAllocatedLine $vatLine) => [
                'taxable_base' => $vatLine->getTaxableBase()->getAmount(),
                'vat_amount' => $vatLine->getVatAmount()->getAmount(),
                'vat_percentage' => $vatLine->getVatPercentage()->get(),
            ], $this->vatLines)),
        ];
    }

    public function getVatLines(): array
    {
        return $this->vatLines;
    }
}
