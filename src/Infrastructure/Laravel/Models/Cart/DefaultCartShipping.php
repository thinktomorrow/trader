<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadShipping;

class DefaultCartShipping extends OrderReadShipping implements CartShipping
{
    public function getShippingProfileId(): string
    {
        return parent::getShippingProfileId();
    }

    public function getProviderId(): string
    {
        return $this->data('provider_id');
    }
}
