<?php

namespace Thinktomorrow\Trader\Payment\Domain;

interface PaymentMethodRepository
{
    public function add(PaymentMethod $paymentMethod);

    public function find(PaymentMethodId $paymentMethodId): PaymentMethod;
}
