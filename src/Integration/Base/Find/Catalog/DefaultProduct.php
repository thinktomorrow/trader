<?php

namespace Thinktomorrow\Trader\Integration\Base\Find;

use Money\Money;
use Money\Currency;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Purchase\PurchasableItem;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;
use Thinktomorrow\Trader\Find\Catalog\Domain\Product;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductId;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItemId;

class DefaultProduct implements Product
{
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

//    use HasMagicAttributes;

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

    public function __construct(ProductId $id, Money $salePrice, TaxRate $taxRate, array $attributes)
    {
        // Todo:: validate integrity of attributes

        $this->id = $id;
        $this->salePrice = $salePrice;
        $this->taxRate = $taxRate;
        $this->currency = $salePrice->getCurrency();

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

    public function id(): ProductId
    {
        return $this->id;
    }

    public function salePrice(): Money
    {
        return $this->salePrice;
    }

    public function price(): Money
    {
        $priceAmount = $this->data('price', $this->salePrice->getAmount());

        return Cash::make($priceAmount, $this->currency);
    }

    public function taxRate(): TaxRate
    {
        // TODO: Implement taxRate() method.
        return $this->taxRate;
    }

    /**
     * Unique reference to the purchasable item
     *
     * @return mixed
     */
    public function purchasableItemId(): PurchasableItemId
    {
        return PurchasableItemId::fromString($this->id()->get());
    }

    /**
     * All the information required for the purchase of this item.
     * This allows to refer to historical accurate item data.
     *
     * @return array
     */
    public function cartItemData(): array
    {
        return [
            'foo' => 'bar',
        ];
    }
}
