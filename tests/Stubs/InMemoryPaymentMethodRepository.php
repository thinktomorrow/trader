<?php

namespace Thinktomorrow\Trader\Tests\Stubs;

use Thinktomorrow\Trader\Payment\Domain\PaymentMethod;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethodId;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethodRepository;

class InMemoryPaymentMethodRepository implements PaymentMethodRepository
{
    private static $collection = [];

    public function find(PaymentMethodId $paymentMethodId): PaymentMethod
    {
        if (isset(self::$collection[(string) $paymentMethodId])) {
            return self::$collection[(string) $paymentMethodId];
        }

        throw new \RuntimeException('PaymentMethod not found by id ['.$paymentMethodId->get().']');
    }

    public function add(PaymentMethod $paymentMethod)
    {
        self::$collection[(string) $paymentMethod->id()] = $paymentMethod;
    }
}
