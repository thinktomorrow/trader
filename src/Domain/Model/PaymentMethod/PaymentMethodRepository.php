<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\PaymentMethod;

interface PaymentMethodRepository
{
    public function save(PaymentMethod $paymentMethod): void;

    public function find(PaymentMethodId $paymentMethodId): PaymentMethod;

    public function delete(PaymentMethodId $paymentMethodId): void;

    public function nextReference(): PaymentMethodId;
}
