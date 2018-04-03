<?php

namespace Thinktomorrow\Trader\Payment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Orders\Domain\Order;

class Country extends BaseCondition implements Condition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['country'])) {
            return true;
        }

        return $this->parameters['country'] == $order->billingAddress('country_key');
    }

    public function getParameterValues(): array
    {
        return [
            'country' => $this->parameters['country']
        ];
    }

    public function setParameterValues(array $values): Condition
    {
        if(!isset($values['country'])){
            throw new \InvalidArgumentException('Raw condition value for country is missing');
        }

        $this->setParameters([
            'country' => $values['country'],
        ]);

        return $this;
    }
}
