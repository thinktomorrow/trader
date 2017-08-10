<?php

namespace App;

use Thinktomorrow\Trader\Catalog\Products\Product as BaseProduct;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;

/**
 * Class Product
 * We can consider Product as a Read-only DTO.
 * The purchasable logic is found in the productVariant itself.
 *
 * @package App
 */
class Product extends BaseProduct
{
    private $name;
    private $defaultVariant;

    public function __construct($name, $defaultVariant)
    {
        $this->name = $name;
        $this->defaultVariant = $defaultVariant;
    }

    public function name()
    {
        return $this->name;
    }

    public function defaultVariantId()
    {
        return $this->defaultVariant->id();
    }

    public function price()
    {
        return (new Cash())->locale($this->defaultVariant->price());
    }
}