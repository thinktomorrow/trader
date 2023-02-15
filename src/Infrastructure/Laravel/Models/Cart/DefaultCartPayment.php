<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadPayment;

class DefaultCartPayment extends OrderReadPayment implements CartPayment
{
    public function getPaymentMethodId(): string
    {
        return parent::getPaymentMethodId();
    }

    public function getProviderId(): string
    {
        return $this->data('provider_id');
    }
}
