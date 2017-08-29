<?php

namespace Thinktomorrow\Trader\Orders\Ports\Reads;

use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;
use Thinktomorrow\Trader\Orders\Application\Reads\Expanded\MerchantOrder;

/**
 * Order presenter for merchant.
 */
class ExpandedOrder extends AbstractPresenter implements MerchantOrder
{
    use CommonOrderValues;

    public function id(): string
    {
        return $this->getValue('id');
    }

    public function reference(): string
    {
        return $this->getValue('reference');
    }

    public function confirmedAt(): string
    {
        return $this->getValue('confirmed_at', null, function ($confirmedAt) {
            return $confirmedAt->format('d/m/Y H:i');
        });
    }
}
