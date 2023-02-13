<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\PaymentMethod;

interface PaymentMethodForCartRepository
{
    /** @return PaymentMethodForCart[] */
    public function findAllPaymentMethodsForCart(?string $countryId = null): array;
}
