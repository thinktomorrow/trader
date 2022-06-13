<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Address;

use Thinktomorrow\Trader\Domain\Common\Address\AddressType;

final class BillingAddress extends OrderAddress
{
    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            'type' => AddressType::billing->value,
        ]);
    }
}
