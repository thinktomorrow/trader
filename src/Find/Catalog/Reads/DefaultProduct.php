<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

use Money\Money;
use Money\Currency;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

class DefaultProduct implements Product
{
    use HasMagicAttributes;

    private $id;

    /** @var Money */
    private $salePrice;

    /** @var Currency */
    private $currency;

    /** @var array */
    private $data;

    /** @var array */
    private $attributes;

    public function __construct($id, Money $salePrice, array $attributes)
    {
        // Todo:: validate integrity of attributes

        $this->id = $id;
        $this->salePrice = $salePrice;
        $this->data = $attributes['data'];
        $this->currency = $salePrice->getCurrency();

        $this->injectAttributes($attributes, ['id', 'data']);

        //        $this->data = (array)json_decode($record->data);
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

    public function id()
    {
        return $this->id;
    }

    public function data($key, $default = null)
    {
        return $this->attr('data.' . $key, $default);
    }

    public function salePrice(): string
    {
         return Cash::from($this->salePrice())->locale($this->attr('attributes.locale'));
    }

    public function salePriceAsMoney(): Money
    {
        return $this->salePrice;
    }

    public function price(): string
    {
        return Cash::from($this->price())->locale($this->attr('attributes.locale'));
    }

    public function priceAsMoney(): Money
    {
        $priceAmount = $this->data('price', $this->salePrice->getAmount());

        return Cash::make($priceAmount, $this->currency);
    }
}
