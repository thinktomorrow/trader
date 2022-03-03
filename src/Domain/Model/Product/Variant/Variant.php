<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

final class Variant implements ChildEntity
{
    use HasData;

    public readonly ProductId $productId;
    public readonly VariantId $variantId;
    private VariantUnitPrice $unitPrice;
    private VariantSalePrice $salePrice;
    private array $options = [];
    private array $personalisations = [];
    private array $data = [];

    private function __construct(){}

    public function getSalePrice(): VariantSalePrice
    {
        return $this->salePrice;
    }

    public static function create(ProductId $productId, VariantId $variantId, VariantUnitPrice $unitPrice, VariantSalePrice $salePrice): static
    {
        $variant = new static();
        $variant->productId = $productId;
        $variant->variantId = $variantId;
        $variant->unitPrice = $unitPrice;
        $variant->salePrice = $salePrice;

        return $variant;
    }

    public function updatePrice(VariantUnitPrice $unitPrice, VariantSalePrice $salePrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->salePrice = $salePrice;
    }

    public function addOrUpdateOption(OptionId $optionId, OptionValueId $optionValueId): void
    {
        $this->options[$optionId->get()] = [
            'option_id' => $optionId,
            'option_value_id' => $optionValueId,
        ];
    }

    public function deleteOption(OptionId $optionId): void
    {
        if(isset($this->options[$optionId->get()])) {
            unset($this->options[$optionId->get()]);
        }
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'variant_id' => $this->variantId->get(),
            'unit_price' => $this->unitPrice->getMoney()->getAmount(),
            'sale_price' => $this->salePrice->getMoney()->getAmount(),
            'tax_rate' => $this->unitPrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->unitPrice->includesTax(),

            'options' => json_encode(array_values(array_map(function(array $option){
                return [
                    'option_id' => $option['option_id']->get(),
                    'option_value_id' => $option['option_value_id']->get(),
                ];
            }, $this->options))),

            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $variant = new static();

        $variant->productId = ProductId::fromString($state['product_id']);
        $variant->variantId = VariantId::fromString($state['variant_id']);
        $variant->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $variant->salePrice = VariantSalePrice::fromScalars($state['sale_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $variant->setOptions(json_decode($state['options']));
        $variant->data = json_decode($state['data']);

        return $variant;
    }

    private function setOptions(array $optionsState): void
    {
        $options = [];

        foreach($optionsState as $optionState) {
            $options[$optionState['option_id']] = [
                'option_id' => OptionId::fromString($optionState['option_id']),
                'option_value_id' => OptionValueId::fromString($optionState['option_value_id']),
            ];
        }

        $this->options = $options;
    }
}
