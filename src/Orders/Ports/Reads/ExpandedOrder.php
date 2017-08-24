<?php

namespace Thinktomorrow\Trader\Orders\Ports\Reads;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;
use Thinktomorrow\Trader\Orders\Application\Reads\Expanded\MerchantOrder;

/**
 * Order presenter for merchant.
 */
class ExpandedOrder extends AbstractPresenter implements MerchantOrder
{
    use CommonOrderValues;

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
