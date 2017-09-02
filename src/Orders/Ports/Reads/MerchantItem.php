<?php

namespace Thinktomorrow\Trader\Orders\Ports\Reads;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;
use Thinktomorrow\Trader\Orders\Application\Reads\Merchant\MerchantItem as MerchantItemContract;

class MerchantItem extends AbstractPresenter implements MerchantItemContract
{
    public function quantity(): int
    {
        return $this->getValue('quantity', 1);
    }

    public function stockBadge(): string
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

    public function price(): string
    {
        return $this->getValue('price', null, function ($price) {
            return Cash::from($price)->locale();
        });
    }

    public function saleprice(): string
    {
        return $this->getValue('saleprice', null, function ($price) {
            return Cash::from($price)->locale();
        });
    }

    public function subtotal(): string
    {
        return $this->getValue('subtotal', null, function ($price) {
            return Cash::from($price)->locale();
        });
    }

    public function total(): string
    {
        return $this->getValue('total', null, function ($price) {
            return Cash::from($price)->locale();
        });
    }

    public function taxRate(): string
    {
        return $this->getValue('taxRate', null, function ($taxRate) {
            return $taxRate->asPercent().'%';
        });
    }
}
