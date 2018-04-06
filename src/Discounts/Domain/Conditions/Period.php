<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use DateTime;
use DateTimeImmutable;
use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\OrderCondition;
use Thinktomorrow\Trader\Orders\Domain\Order;

class Period extends BaseCondition implements Condition, OrderCondition
{
    public function check(Order $order): bool
    {
        $valid_start_at = $this->comesAfter(
            new DateTimeImmutable(),
            isset($this->parameters['start_at']) ? $this->parameters['start_at'] : null
        );

        $valid_end_at = $this->goesBefore(
            new DateTimeImmutable(),
            isset($this->parameters['end_at']) ? $this->parameters['end_at'] : null
        );

        return true == $valid_start_at && true == $valid_end_at;
    }

    private function comesAfter(DateTimeImmutable $datetime, DateTimeImmutable $startAt = null)
    {
        if (!$startAt) {
            return true;
        }

        return $startAt < $datetime;
    }

    private function goesBefore(DateTimeImmutable $datetime, DateTimeImmutable $endAt = null)
    {
        if (!$endAt) {
            return true;
        }

        return $endAt > $datetime;
    }

    public function getParameterValues(): array
    {
        return [
            'start_at' => $this->parameters['start_at']->format('Y-m-d H:i:s'),
            'end_at' => $this->parameters['end_at']->format('Y-m-d H:i:s')
        ];
    }

    public function setParameterValues(array $values): Condition
    {
        $this->setParameters([
            'start_at' => $values['start_at'] ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $values['start_at']) : null,
            'end_at' => $values['end_at'] ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $values['end_at']) : null,
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
        if (isset($parameters['start_at']) && !$parameters['start_at'] instanceof DateTimeImmutable) {
            throw new \InvalidArgumentException('DiscountCondition value for start_at must be instance of DateTimeImmutable.');
        }

        if (isset($parameters['end_at']) && !$parameters['end_at'] instanceof DateTimeImmutable) {
            throw new \InvalidArgumentException('DiscountCondition value for end_at must be instance of DateTimeImmutable.');
        }
    }
}
