<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Order\Domain\Purchasable;
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
     * Do the sale conditions apply for the given purchasable
     *
     * @param Purchasable $purchasable
     * @return bool
     */
    public function applicable(Purchasable $purchasable): bool
    {
        foreach($this->conditions as $condition)
        {
            if(false == $condition->check($purchasable)) return false;
        }

        return true;
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