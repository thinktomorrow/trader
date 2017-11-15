<?php

namespace Thinktomorrow\Trader\Orders\Ports\Read;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Common\Ports\Web\AbstractPresenter;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantItem as MerchantItemContract;

class MerchantItem extends AbstractPresenter implements MerchantItemContract
{
    public function purchasableId(): int
    {
        return $this->getValue('purchasable_id');
    }

    public function purchasableType(): string
    {
        return $this->getValue('purchasable_type');
    }

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

    public function onSale(): bool
    {
        return (bool) $this->getValue('onsale');
    }

    public function saleprice(): string
    {
        if(($salePrice = $this->getValue('saleprice')) && $salePrice->isPositive())
        {
            return Cash::from($salePrice)->locale();
        }

        return $this->price();
    }

    public function salePriceAmount(): string
    {
        if(($salePrice = $this->getValue('saleprice')) && $salePrice->isPositive())
        {
            return (string) $salePrice->getAmount();
        }

        return (string) $this->getValue('price')->getAmount();
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
        return $this->getValue('taxRate', '', function ($taxRate) {
            return $taxRate->asPercent().'%';
        });
    }
}
