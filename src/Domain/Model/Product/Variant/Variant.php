<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

final class Variant implements ChildEntity
{
    use HasData;

    public readonly ProductId $productId;
    public readonly VariantId $variantId;
    private VariantState $state;
    private VariantUnitPrice $unitPrice;
    private VariantSalePrice $salePrice;
    private array $optionValueIds = [];
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
        $variant->state = VariantState::available;
        $variant->productId = $productId;
        $variant->variantId = $variantId;
        $variant->unitPrice = $unitPrice;
        $variant->salePrice = $salePrice;

        return $variant;
    }

    public function updateState(VariantState $state): void
    {
        $this->state = $state;
    }

    public function updatePrice(VariantUnitPrice $unitPrice, VariantSalePrice $salePrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->salePrice = $salePrice;
    }

    public function updateOptionValueIds(array $optionValueIds): void
    {
        Assertion::allIsInstanceOf($optionValueIds, OptionValueId::class);

        $this->optionValueIds = $optionValueIds;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'variant_id' => $this->variantId->get(),
            'state' => $this->state->value,
            'unit_price' => $this->unitPrice->getMoney()->getAmount(),
            'sale_price' => $this->salePrice->getMoney()->getAmount(),
            'tax_rate' => $this->unitPrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->unitPrice->includesVat(),
            'option_value_ids' => array_map(fn($optionValueId) => $optionValueId->get(), $this->optionValueIds),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $variant = new static();

        $variant->productId = ProductId::fromString($aggregateState['product_id']);
        $variant->variantId = VariantId::fromString($state['variant_id']);
        $variant->state = VariantState::from($state['state']);
        $variant->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $variant->salePrice = VariantSalePrice::fromScalars($state['sale_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $variant->data = json_decode($state['data'], true);

        $variant->optionValueIds = array_map(fn($optionValueState) => OptionValueId::fromString($optionValueState), $state['option_value_ids']);

        return $variant;
    }

}
