<?php

namespace Thinktomorrow\Trader\Sales\Domain;

interface Sale
{
    public function id(): SaleId;

    public function applicable(EligibleForSale $eligibleForSale): bool;

    public function apply(EligibleForSale $eligibleForSale);
}
