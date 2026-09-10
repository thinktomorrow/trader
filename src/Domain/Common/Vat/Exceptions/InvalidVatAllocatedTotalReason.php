<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat\Exceptions;

enum InvalidVatAllocatedTotalReason: string
{
    case TotalEquationMismatch = 'total_equation_mismatch';
    case TaxableBaseSumMismatch = 'taxable_base_sum_mismatch';
    case VatAmountSumMismatch = 'vat_amount_sum_mismatch';
}
