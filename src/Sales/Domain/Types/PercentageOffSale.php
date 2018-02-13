<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\Sale;

class PercentageOffSale extends BaseSale implements Sale
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
            $this->adjusters['percentage'],
            $this->data
        ));
    }

    public function saleAmount(EligibleForSale $eligibleForSale): Money
    {
        return $eligibleForSale->price()->multiply($this->adjusters['percentage']->asFloat());
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        parent::validateParameters($conditions, $adjusters);

        if (!isset($adjusters['percentage'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'percentage\', required for sale '.get_class($this));
        }
        if (!$adjusters['percentage'] instanceof Percentage) {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for sale '.get_class($this).'. Instance of '.Percentage::class.' is expected.');
        }

        if ($adjusters['percentage']->asPercent() > 100) {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for sale '.get_class($this).'. Percentage cannot be higher than 100%. ['.$adjusters['percentage']->asPercent().'%] given.');
        }

        if ($adjusters['percentage']->asPercent() < 0) {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for sale '.get_class($this).'. Percentage cannot be lower than 0%. ['.$adjusters['percentage']->asPercent().'%] given.');
        }
    }
}
