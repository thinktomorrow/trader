<?php

namespace Thinktomorrow\Trader\Discounts\Ports\Web;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;

class Discount extends AbstractPresenter
{
    public function description()
    {
        return $this->getValue('description',null,function($description){
            return 'crazy discount: '.print_r($description->values(),true);
        });
    }

    public function amount()
    {
        return $this->getValue('amount',null,function($amount){
            return (new Cash())->locale($amount);
        });
    }
}