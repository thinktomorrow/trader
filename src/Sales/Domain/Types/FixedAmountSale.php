<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Adjuster;
use Thinktomorrow\Trader\Common\Adjusters\Amount;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\Sale;

class FixedAmountSale extends BaseSale implements Sale
{
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
        return $eligibleForSale->price()->subtract($this->adjuster->getParameter('amount'));
    }

    protected function validateParameters(array $conditions, Adjuster $adjuster)
    {
        parent::validateParameters($conditions, $adjuster);

        Assertion::isInstanceOf($adjuster, Amount::class);
    }
}
