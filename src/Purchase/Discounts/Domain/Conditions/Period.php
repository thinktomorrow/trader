<?php

namespace Optiphar\Discounts\Conditions;

use DateTimeImmutable;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\EligibleForDiscount;
use Optiphar\Promos\Common\Domain\Rules\Rule;

class Period implements Condition
{
    /** @var DateTimeImmutable */
    private $startAt;

    /** @var DateTimeImmutable */
    private $endAt;

    public function __construct(\DateTimeImmutable $startAt, \DateTimeImmutable $endAt)
    {
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    public function check(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        $valid_start_at = $this->comesAfter( new DateTimeImmutable(), $this->startAt );
        $valid_end_at = $this->goesBefore( new DateTimeImmutable(), $this->endAt );

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

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromPeriodRule($rule);
    }

    private static function fromPeriodRule(\Optiphar\Promos\Common\Domain\Rules\Period $rule)
    {
        return new static(
            DateTimeImmutable::createFromMutable($rule->getStart()),
            DateTimeImmutable::createFromMutable($rule->getEnd())
        );
    }

    public function toArray(): array
    {
        return [
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
        ];
    }
}
