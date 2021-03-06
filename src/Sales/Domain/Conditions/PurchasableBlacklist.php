<?php

namespace Thinktomorrow\Trader\Sales\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\BaseCondition;
use Thinktomorrow\Trader\Orders\Domain\Purchasable;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;

class PurchasableBlacklist extends BaseCondition implements SaleCondition
{
    public function check(EligibleForSale $eligibleForSale): bool
    {
        if (!isset($this->parameters['purchasable_blacklist'])) {
            return true;
        }

        return $this->checkPurchasable($eligibleForSale);
    }

    private function checkPurchasable(Purchasable $purchasable): bool
    {
        return !in_array($purchasable->purchasableId()->get(), $this->parameters['purchasable_blacklist']);
    }

    /**
     * Validation of required parameters.
     *
     * @param $parameters
     */
    protected function validateParameters(array $parameters)
    {
        if (isset($parameters['purchasable_blacklist']) && !is_array($parameters['purchasable_blacklist'])) {
            throw new \InvalidArgumentException('Condition value for purchasable_blacklist must be an array of ids. '.gettype($parameters['purchasable_blacklist']).' given.');
        }
    }
}
