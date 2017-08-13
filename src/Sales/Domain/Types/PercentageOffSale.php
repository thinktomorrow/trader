<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

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
        $saleAmount = $eligibleForSale->price()->multiply($this->adjusters['percentage']->asFloat());

        if(!$this->applicable($eligibleForSale))
        {
            throw new CannotApplySale('Sale cannot be applied. [Sale of '. (new Cash())->locale($saleAmount).', current saleTotal: '.(new Cash())->locale($eligibleForSale->saleTotal()).'] cannot be added to price ['.(new Cash())->locale($eligibleForSale->price()).']');
        }

        $eligibleForSale->addToSaleTotal($saleAmount);
        $eligibleForSale->addSale(new AppliedSale());
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

        if($adjusters['percentage']->asPercent() > 100)
        {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for sale '.get_class($this).'. Percentage cannot be higher than 100%. ['.$adjusters['percentage']->asPercent().'%] given.');
        }

        if($adjusters['percentage']->asPercent() < 0)
        {
            throw new \InvalidArgumentException('Invalid adjuster value \'percentage\' for sale '.get_class($this).'. Percentage cannot be lower than 0%. ['.$adjusters['percentage']->asPercent().'%] given.');
        }
    }
}