<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Adjusters\Adjuster;
use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Sales\Domain\Conditions\ConditionKey;
use Thinktomorrow\Trader\Sales\Domain\Conditions\SaleCondition;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;

abstract class BaseSale
{
    /** @var SaleId */
    protected $id;

    /** @var Condition[] */
    protected $conditions;

    /** @var Adjuster */
    protected $adjuster;

    /** @var array */
    protected $data;

    public function __construct(SaleId $id, array $conditions, Adjuster $adjuster, array $data = [])
    {
        $this->validateParameters($conditions, $adjuster);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->adjuster = $adjuster;

        // Custom data, e.g. sales text for display on site.
        $this->data = $data;
    }

    public function id(): SaleId
    {
        return $this->id;
    }

    /**
     * Do the sale conditions apply for the given owner.
     *
     * @param EligibleForSale $eligibleForSale
     *
     * @return bool
     */
    public function applicable(EligibleForSale $eligibleForSale): bool
    {
        if ($this->greaterThanPrice($eligibleForSale) || $this->saleAmountBelowZero($eligibleForSale)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (false == $condition->check($eligibleForSale)) {
                return false;
            }
        }

        return true;
    }

    public function usesCondition(string $condition_key): bool
    {
        foreach ($this->conditions as $condition) {
            if ($this->getConditionKey($condition_key)->equalsClass($condition)) {
                return true;
            }
        }

        return false;
    }

    private function greaterThanPrice(EligibleForSale $eligibleForSale)
    {
        // SaleTotal cannot be higher than original price
        $saleTotal = $eligibleForSale->saleTotal()->add($this->saleAmount($eligibleForSale));

        return $saleTotal->greaterThan($eligibleForSale->price());
    }

    private function saleAmountBelowZero(EligibleForSale $eligibleForSale)
    {
        $saleTotal = $eligibleForSale->saleTotal()->add($this->saleAmount($eligibleForSale));

        return $saleTotal->isNegative();
    }

    /**
     * @param array    $conditions
     * @param Adjuster $adjuster
     */
    protected function validateParameters(array $conditions, Adjuster $adjuster)
    {
        Assertion::allIsInstanceOf($conditions, SaleCondition::class);
    }

    protected function getCondition(string $condition_key)
    {
        if (!isset($this->conditions[$condition_key])) {
            return;
        }

        return $this->conditions[$condition_key];
    }

    protected function getType(): string
    {
        return TypeKey::fromSale($this)->get();
    }

    protected function getConditionKey($string): ConditionKey
    {
        return ConditionKey::fromString($string);
    }
}
