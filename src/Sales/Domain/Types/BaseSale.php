<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;

abstract class BaseSale
{
    /**
     * @var SaleId
     */
    protected $id;

    /**
     * @var Condition[]
     */
    protected $conditions;

    /**
     * @var array
     */
    protected $adjusters;

    public function __construct(SaleId $id, array $conditions, array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->adjusters = $adjusters;
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
        if (!$this->lessThanPrice($eligibleForSale)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (false == $condition->check($eligibleForSale)) {
                return false;
            }
        }

        return true;
    }

    private function lessThanPrice(EligibleForSale $eligibleForSale)
    {
        $saleAmount = $eligibleForSale->price()->multiply($this->adjusters['percentage']->asFloat());

        // SaleTotal cannot be higher than original price
        $saleTotal = $eligibleForSale->saleTotal()->add($saleAmount);

        return !($saleTotal->greaterThan($eligibleForSale->price()));
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);
    }
}