<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class DefaultVariantForCart implements VariantForCart
{
    use RendersData;

    private VariantId $variantId;
    private ProductId $productId;
    private VariantState $state;
    private VariantUnitPrice $variantUnitPrice;
    private VariantSalePrice $variantSalePrice;
    private array $personalisations;
    private array $productData;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $personalisations): static
    {
        $object = new static();

        $object->variantId = VariantId::fromString($state['variant_id']);
        $object->productId = ProductId::fromString($state['product_id']);
        $object->state = VariantState::from($state['state']);
        $object->variantUnitPrice = VariantUnitPrice::fromScalars($state['unit_price'], $state['tax_rate'], $state['includes_vat']);
        $object->variantSalePrice = VariantSalePrice::fromScalars($state['sale_price'], $state['tax_rate'], $state['includes_vat']);
        $object->personalisations = $personalisations;
        $object->data = json_decode($state['data'], true);
        $object->productData = json_decode($state['product_data'], true);

        return $object;
    }

    public function getVariantId(): VariantId
    {
        return $this->variantId;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getState(): VariantState
    {
        return $this->state;
    }

    public function getUnitPrice(): VariantUnitPrice
    {
        return $this->variantUnitPrice;
    }

    public function getSalePrice(): VariantSalePrice
    {
        return $this->variantSalePrice;
    }

    public function getTitle(): string
    {
        if($customTitle = $this->data('title')) {
            return $customTitle;
        }

        $productTitle = $this->data('title', null, '', $this->productData);

        return ($productTitle ? $productTitle .' ' : '') . $this->data('option_title', null, '');
    }

    public function getPersonalisations(): array
    {
        return $this->personalisations;
    }
}
