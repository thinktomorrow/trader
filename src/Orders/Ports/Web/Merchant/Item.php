<?php

namespace Thinktomorrow\Trader\Orders\Ports\Web\Merchant;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;

class Item extends AbstractPresenter
{
    public function quantity()
    {
        return $this->getValue('quantity', 1);
    }

    public function stockBadge()
    {
        return $this->getValue('stock', null, function ($stock) {

            // TODO translate this

            $flair = 'danger';
            $text = 'niet op voorraad';

            if ($stock > 0) {
                $flair = 'success';
                $text = 'op voorraad';
            }
            if ($this->getValue('stock_warning', false)) {
                $flair = 'warning';
                $text = 'bijna uit voorraad';
            }

            return "<span class='label label-{$flair}'>{$text}</span>";
        });
    }

    public function price()
    {
        return $this->getValue('price', null, function ($price) {
            return (new Cash())->locale($price);
        });
    }

    public function saleprice()
    {
        return $this->getValue('saleprice', null, function ($price) {
            return (new Cash())->locale($price);
        });
    }

    public function subtotal()
    {
        return $this->getValue('subtotal', null, function ($price) {
            return (new Cash())->locale($price);
        });
    }

    public function total()
    {
        return $this->getValue('total', null, function ($price) {
            return (new Cash())->locale($price);
        });
    }

    public function taxRate()
    {
        return $this->getValue('taxRate', null, function ($taxRate) {
            return $taxRate->asPercent().'%';
        });
    }
}
