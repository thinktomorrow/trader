<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use DateTimeImmutable;
use Thinktomorrow\Trader\Discounts\Domain\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Order\Domain\Order;

class Period implements Condition
{
    private DateTimeImmutable $startAt;
    private DateTimeImmutable $endAt;

    public function __construct(\DateTimeImmutable $startAt, \DateTimeImmutable $endAt)
    {
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        $valid_start_at = $this->comesAfter(new DateTimeImmutable(), $this->startAt);
        $valid_end_at = $this->goesBefore(new DateTimeImmutable(), $this->endAt);

        return true == $valid_start_at && true == $valid_end_at;
    }

    private function comesAfter(DateTimeImmutable $datetime, DateTimeImmutable $startAt = null): bool
    {
        if (! $startAt) {
            return true;
        }

        return $startAt < $datetime;
    }

    private function goesBefore(DateTimeImmutable $datetime, DateTimeImmutable $endAt = null): bool
    {
        if (! $endAt) {
            return true;
        }

        return $endAt > $datetime;
    }

//    public static function fromRule(Rule $rule, array $data = []): Condition
//    {
//        return static::fromPeriodRule($rule);
//    }
//
//    private static function fromPeriodRule(\Optiphar\Promos\Common\Domain\Rules\Period $rule)
//    {
//        return new static(
//            DateTimeImmutable::createFromMutable($rule->getStart()),
//            DateTimeImmutable::createFromMutable($rule->getEnd())
//        );
//    }
//
//    public function toArray(): array
//    {
//        return [
//            'start_at' => $this->startAt,
//            'end_at' => $this->endAt,
//        ];
//    }
}
