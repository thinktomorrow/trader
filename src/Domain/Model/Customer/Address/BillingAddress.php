<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer\Address;

use Thinktomorrow\Trader\Domain\Common\Address\AddressType;

class BillingAddress extends CustomerAddress
{
    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            'type' => AddressType::billing->value,
        ]);
    }
}
