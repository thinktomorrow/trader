<?php

namespace Thinktomorrow\Trader\Sales\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\SaleCondition;
use Thinktomorrow\Trader\Orders\Domain\Purchasable;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;

class PurchasableWhitelist extends BaseCondition implements Condition, SaleCondition
{
    public function check(EligibleForSale $eligibleForSale): bool
    {
        if (!isset($this->parameters['purchasable_whitelist'])) {
            return true;
        }

        return $this->checkPurchasable($eligibleForSale);
    }

    private function checkPurchasable(Purchasable $purchasable): bool
    {
        return in_array($purchasable->purchasableId()->get(), $this->parameters['purchasable_whitelist']);
    }

    public function getParameterValues(): array
    {
        return [
            'purchasable_whitelist' => $this->parameters['purchasable_whitelist']
        ];
    }

    public function setParameterValues(array $values): Condition
    {
        if(!isset($values['purchasable_whitelist'])){
            throw new \InvalidArgumentException('Raw condition value for purchasable_whitelist is missing');
        }

        $this->setParameters([
            'purchasable_whitelist' => $values['purchasable_whitelist'],
        ]);

        return $this;
    }

    /**
     * Validation of required parameters.
     *
     * @param $parameters
     */
    protected function validateParameters(array $parameters)
    {
        if (isset($parameters['purchasable_whitelist']) && !is_array($parameters['purchasable_whitelist'])) {
            throw new \InvalidArgumentException('Condition value for purchasable_whitelist must be an array of ids. '.gettype($parameters['purchasable_whitelist']).' given.');
        }
    }
}
