<?php

namespace Thinktomorrow\Trader\Common\Adjusters;

use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;

class Percentage extends BaseAdjuster implements Adjuster
{
    public function getParameterValues(): array
    {
        return [
            'percentage' => $this->parameters['percentage']->asPercent(),
        ];
    }

    public function setParameterValues($values): HasParameters
    {
        $values = $this->normalizeParameters($values);

        $this->setParameters([
            'percentage' => PercentageValue::fromPercent($values['percentage']),
        ]);

        return $this;
    }

    protected function validateParameters(array $parameters)
    {
        if (!isset($parameters['percentage'])) {
            throw new \InvalidArgumentException('Missing value \'percentage\', required for adjuster '.get_class($this));
        }

        if (!$parameters['percentage'] instanceof PercentageValue) {
            throw new \InvalidArgumentException('Invalid value \'percentage\' for adjuster '.get_class($this).'. Instance of '.PercentageValue::class.' is expected.');
        }

        if ($parameters['percentage']->asPercent() > 100) {
            throw new \InvalidArgumentException('Invalid value \'percentage\' for '.get_class($this).'. Percentage cannot be higher than 100%. ['.$parameters['percentage']->asPercent().'%] given.');
        }

        if ($parameters['percentage']->asPercent() < 0) {
            throw new \InvalidArgumentException('Invalid value \'percentage\' for '.get_class($this).'. Percentage cannot be lower than 0%. ['.$parameters['percentage']->asPercent().'%] given.');
        }
    }
}
