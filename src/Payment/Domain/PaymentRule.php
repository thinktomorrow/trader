<?php

namespace Thinktomorrow\Trader\Payment\Domain;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Orders\Domain\Order;

class PaymentRule
{
    /**
     * @var PaymentRuleId
     */
    private $id;

    /**
     * @var array
     */
    private $conditions;

    /**
     * @var array
     */
    private $adjusters;

    /**
     * @var Money
     */
    private $amount;

    public function __construct(PaymentRuleId $id, array $conditions, array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->amount = $adjusters['amount'];
        $this->adjusters = $adjusters;
    }

    public function applicable(Order $order): bool
    {
        foreach ($this->conditions as $condition) {
            if (false == $condition->check($order)) {
                return false;
            }
        }

        return true;
    }

    public function id(): PaymentRuleId
    {
        return $this->id;
    }

    public function conditions(): array
    {
        return $this->conditions;
    }

    public function total(): Money
    {
        return $this->amount;
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    private function validateParameters(array $conditions, array $adjusters)
    {
        Assertion::allIsInstanceOf($conditions, HasParameters::class);

        if (!isset($adjusters['amount']) || !$adjusters['amount'] instanceof Money) {
            throw new \InvalidArgumentException('Invalid or missing amount. paymentRule requires at least an amount adjuster of type Money.');
        }
    }
}
