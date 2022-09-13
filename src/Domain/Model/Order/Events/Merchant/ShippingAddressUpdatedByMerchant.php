<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class ShippingAddressUpdatedByMerchant
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly array $updatedValues,
        public readonly array $data
    ) {
    }
}
