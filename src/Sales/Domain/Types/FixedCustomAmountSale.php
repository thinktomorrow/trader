<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\Sale;

class FixedCustomAmountSale extends BaseSale implements Sale
{
    public function applicable(EligibleForSale $eligibleForSale): bool
    {
        $applicable = parent::applicable($eligibleForSale);

        return ($applicable && $eligibleForSale->hasOriginalSalePrice());
    }

    public function apply(EligibleForSale $eligibleForSale)
    {
        $saleAmount = $this->saleAmount($eligibleForSale);

        if (!$this->applicable($eligibleForSale)) {
            throw new CannotApplySale('Sale cannot be applied. [Sale of '.Cash::from($saleAmount)->locale().', current saleTotal: '.Cash::from($eligibleForSale->saleTotal())->locale().'] cannot be added to price ['.Cash::from($eligibleForSale->price())->locale().']');
        }

        $eligibleForSale->addToSaleTotal($saleAmount);
        $eligibleForSale->addSale(new AppliedSale(
            $this->id,
            TypeKey::fromSale($this)->get(),
            $saleAmount,
            Cash::from($saleAmount)->asPercentage($eligibleForSale->price(), 0),
            $this->data
        ));
    }

    public function saleAmount(EligibleForSale $eligibleForSale): Money
    {
        if( ! $eligibleForSale->hasOriginalSalePrice()) return Cash::zero();

        return $eligibleForSale->price()->subtract($eligibleForSale->originalSalePrice());
    }
}
