<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

use Money\Money;
use Money\Currency;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;

class DefaultProductRead implements ProductRead
{
    use HasMagicAttributes, RendersMoney;

    // title, description, seo
    // images
    // variants: image, option, price, amount, sku
    // channel availability
    // type
    // vendor
    // collections (category pages)
    // tags
    // sales

    // extra:
    // product series
    // product line
    // custom attribute: cnk (unique)
    // ean
    // product bundle

    /** @var ChannelId */
    private $channelId;

    /** @var LocaleId */
    private $localeId;

    /** @var ProductId */
    private $id;

    /** @var Money */
    private $salePrice;

    /** @var TaxRate */
    private $taxRate;

    /** @var Currency */
    private $currency;

    /** @var array */
    private $attributes;

    public function __construct(ChannelId $channelId, LocaleId $localeId, ProductId $id, Money $salePrice, TaxRate $taxRate, array $attributes)
    {
        $this->channelId = $channelId;
        $this->localeId = $localeId;

        $this->id = $id;
        $this->salePrice = $salePrice;
        $this->taxRate = $taxRate;
        $this->currency = $salePrice->getCurrency();

        // Channel and locale ...

        $this->injectAttributes($attributes);
    }

    private function injectAttributes(array $attributes, array $blacklist = [])
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $blacklist)) {
                continue;
            }
            $this->attributes[$key] = $value;
        }
    }

    public function id(): string
    {
        return $this->id->get();
    }

    public function salePrice(): string
    {
        return $this->renderMoney($this->salePrice, $this->localeId);
    }

    public function price(): string
    {
        $priceAmount = $this->data('price', $this->salePrice->getAmount());

        return $this->renderMoney(Cash::make($priceAmount, $this->currency), $this->localeId);
    }

    public function taxRate(): string
    {
        return $this->renderPercentage($this->taxRate);
    }

    public function url(): string
    {
        return '/products/'.$this->id();
    }

    public function buyUrl(): string
    {
        return '/buy-product/'.$this->id();
    }

    public function data($key, $default = null)
    {
        return $this->attr('attributes.'.$key, $default);
    }
}
