<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use DateTime;
use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\OrderCondition;
use Thinktomorrow\Trader\Order\Domain\Order;

class Period extends BaseCondition implements Condition, OrderCondition
{
    public function check(Order $order): bool
    {
        $valid_start_at = $this->comesAfter(
            new DateTime,
            isset($this->parameters['start_at']) ? $this->parameters['start_at'] : null
        );

        $valid_end_at = $this->goesBefore(
            new DateTime,
            isset($this->parameters['end_at']) ? $this->parameters['end_at'] : null
        );

        return (true == $valid_start_at && true == $valid_end_at);
    }

    private function comesAfter(DateTime $datetime, DateTime $startAt = null)
    {
        if(!$startAt) return true;

        return $startAt < $datetime;
    }

    private function goesBefore(DateTime $datetime, DateTime $endAt = null)
    {
        if(!$endAt) return true;

        return $endAt > $datetime;
    }

    /**
     * Validation of required parameters
     *
     * @param $parameters
     */
    protected function validateParameters(array $parameters)
    {
        if(isset($parameters['start_at']) && ! $parameters['start_at'] instanceof \DateTime)
        {
            throw new \InvalidArgumentException('DiscountCondition value for start_at must be instance of DateTime.');
        }

        if(isset($parameters['end_at']) && ! $parameters['end_at'] instanceof \DateTime)
        {
            throw new \InvalidArgumentException('DiscountCondition value for end_at must be instance of DateTime.');
        }
    }
}