<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use DateTime;
use Thinktomorrow\Trader\Order\Domain\Order;

class Period implements Condition
{
    public function check(array $conditions, Order $order): bool
    {
        $valid_start_at = $this->comesAfterStartAt(
            new DateTime,
            isset($conditions['start_at']) ? $conditions['start_at'] : null
        );

        $valid_end_at = $this->comesBeforeEndAt(
            new DateTime,
            isset($conditions['end_at']) ? $conditions['end_at'] : null
        );

        return (true == $valid_start_at && true == $valid_end_at);
    }

    private function comesAfterStartAt(DateTime $datetime, DateTime $startAt = null)
    {
        if(!$startAt) return true;

        return $startAt < $datetime;
    }

    private function comesBeforeEndAt(DateTime $datetime, DateTime $endAt = null)
    {
        if(!$endAt) return true;

        return $endAt > $datetime;
    }
}