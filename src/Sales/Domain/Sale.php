<?php

namespace Thinktomorrow\Trader\Sales\Domain;

use Money\Money;

interface Sale
{
    public function id(): SaleId;

    public function applicable(EligibleForSale $eligibleForSale): bool;

    public function apply(EligibleForSale $eligibleForSale);

    public function saleAmount(EligibleForSale $eligibleForSale): Money;

    public function usesCondition(string $condition_key): bool;
}
