<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;

final readonly class SelectedServiceProfiles
{
    public function __construct(
        public ?ShippingProfile $shipping,
        public ?PaymentMethod $payment,
    ) {}
}
