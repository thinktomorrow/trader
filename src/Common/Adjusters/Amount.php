<?php

namespace Thinktomorrow\Trader\Common\Adjusters;

use Money\Money;
use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Price\Cash;

class Amount extends BaseAdjuster implements Adjuster
{
    public function getRawParameters(): array
    {
        return [
            'amount' => $this->parameters['amount']->getAmount()
        ];
    }

    public function setRawParameters($values): HasParameters
    {
        $values = $this->normalizeParameters($values);

        $this->setParameters([
            'amount' => Cash::make($values['amount']),
        ]);

        return $this;
    }

    protected function validateParameters(array $parameters)
    {
        if (!isset($parameters['amount'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'amount\', required for adjuster '.get_class($this));
        }
        if (!$parameters['amount'] instanceof Money) {
            throw new \InvalidArgumentException('Invalid adjuster value ['.gettype($parameters['amount']).'] \'amount\' for adjuster. Instance of '.Money::class.' is expected.');
        }
        if ($parameters['amount']->isNegative()) {
            throw new \InvalidArgumentException('Adjuster value \'amount\' cannot be negative. '.$parameters['amount']->getAmount().' is given.');
        }
    }
}