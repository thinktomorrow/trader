<?php

namespace Thinktomorrow\Trader\Payment\Domain;

use Assert\Assertion;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Payment\Domain\Exceptions\CannotApplyPaymentRule;

class PaymentMethod
{
    /**
     * @var PaymentMethodId
     */
    private $id;

    /**
     * @var PaymentRule[]
     */
    private $rules;

    /**
     * Request cache of matching rule (avoid redundant matching logic).
     *
     * @var PaymentRule
     */
    private $applicableRule;

    /**
     * Unique string identifier
     * @var string
     */
    private $code;

    public function __construct(PaymentMethodId $id, string $code, array $rules = [])
    {
        Assertion::allIsInstanceOf($rules, PaymentRule::class);

        $this->id = $id;
        $this->code = $code;
        $this->rules = $rules;
    }

    public function id(): PaymentMethodId
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function apply(Order $order)
    {
        if (!$this->applicable($order)) {
            throw new CannotApplyPaymentRule('payment method ['.$this->id().'] not applicable to order ['.$order->id().']. None of the rules match.');
        }

        $paymentRule = $this->getApplicableRule($order);

        $order->setpayment($this->id(), $paymentRule->id());

        // TODO: Adjuster should be altering the order, not straight in the paymentMethod. This way the adjustment logic
        // Is more flexible and also usable for discounts, sales,...
        $order->setPaymentTotal($paymentRule->total());
    }

    public function applicable(Order $order): bool
    {
        return (bool) $this->getApplicableRule($order);
    }

    private function getApplicableRule(Order $order)
    {
        // Return early if rule has already been determined
        if ($this->applicableRule) {
            return $this->applicableRule;
        }

        foreach ($this->rules as $rule) {
            if ($rule->applicable($order)) {
                return $this->applicableRule = $rule;
            }
        }
    }
}
