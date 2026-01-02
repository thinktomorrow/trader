<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;

trait WithVatLines
{
    /** @var VatAllocatedLine[] */
    private array $vatLines = [];

    public function getVatLines(): array
    {
        return $this->vatLines;
    }
}
