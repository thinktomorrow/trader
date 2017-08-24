<?php

namespace Thinktomorrow\Trader\Discounts\Ports\Reads;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;

class Discount extends AbstractPresenter implements \Thinktomorrow\Trader\Discounts\Application\Reads\Discount
{
    public function description(): string
    {
        return $this->getValue('description', null, function ($description) {
            return 'crazy discount: '.print_r($description->values(), true);
        });
    }

    public function amount(): string
    {
        return $this->getValue('amount', null, function ($amount) {
            return Cash::from($amount)->locale();
        });
    }
}
