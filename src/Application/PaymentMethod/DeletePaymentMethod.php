<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\PaymentMethod;

use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class DeletePaymentMethod
{
    private string $paymentMethodId;

    public function __construct(string $paymentMethodId)
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getPaymentMethodId(): PaymentMethodId
    {
        return PaymentMethodId::fromString($this->paymentMethodId);
    }
}
