<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\Sale;

class FixedAmountOffSale extends BaseSale implements Sale
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
            get_class($this),
            $saleAmount,
            Cash::from($saleAmount)->asPercentage($eligibleForSale->price(), 0),
            $this->data
        ));
    }

    public function saleAmount(EligibleForSale $eligibleForSale): Money
    {
        return $this->adjusters['amount'];
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        parent::validateParameters($conditions, $adjusters);

        if (!isset($adjusters['amount'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'amount\', required for sale '.get_class($this));
        }
        if (!$adjusters['amount'] instanceof Money) {
            throw new \InvalidArgumentException('Invalid adjuster value \'amount\' for sale '.get_class($this).'. Instance of '.Money::class.' is expected.');
        }

        if ($adjusters['amount']->getAmount() < 0) {
            throw new \InvalidArgumentException('Invalid adjuster value \'amount\' for sale '.get_class($this).'. Percentage cannot be lower than 0. ['.$adjusters['amount']->getAmount().'] given.');
        }
    }
}
