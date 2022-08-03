<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOptionLink;

final class Variant implements ChildEntity
{
    use HasData;

    public readonly ProductId $productId;
    public readonly VariantId $variantId;
    private VariantState $state;
    private VariantUnitPrice $unitPrice;
    private VariantSalePrice $salePrice; // bedrag, btw perc, bool includes_tax?

    /** @var DefaultOptionLink[] */
    private array $optionValueIds = [];
    private array $personalisations = [];
    private string $sku;
    private bool $show_in_grid = false;

    private function __construct()
    {
    }

    public function getSalePrice(): VariantSalePrice
    {
        return $this->salePrice;
    }

    public function getUnitPrice(): VariantUnitPrice
    {
        return $this->unitPrice;
    }

    public static function create(ProductId $productId, VariantId $variantId, VariantUnitPrice $unitPrice, VariantSalePrice $salePrice, string $sku): static
    {
        $variant = new static();
        $variant->state = VariantState::available;
        $variant->productId = $productId;
        $variant->variantId = $variantId;
        $variant->unitPrice = $unitPrice;
        $variant->salePrice = $salePrice;
        $variant->sku = $sku;

        return $variant;
    }

    public function updateState(VariantState $state): void
    {
        $this->state = $state;
    }

    public function getState(): VariantState
    {
        return $this->state;
    }

    public function updatePrice(VariantUnitPrice $unitPrice, VariantSalePrice $salePrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->salePrice = $salePrice;
    }

    public function showInGrid(bool $show_in_grid = true): void
    {
        $this->show_in_grid = $show_in_grid;
    }

    public function updateOptionValueIds(array $optionValueIds): void
    {
        Assertion::allIsInstanceOf($optionValueIds, OptionValueId::class);

        $this->optionValueIds = $optionValueIds;
    }

    public function getOptionValueIds(): array
    {
        return $this->optionValueIds;
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
            'sku' => $this->sku,
            'option_value_ids' => array_map(fn ($optionValueId) => $optionValueId->get(), $this->optionValueIds),
            'show_in_grid' => $this->show_in_grid,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $variant = new static();

        $variant->productId = ProductId::fromString($aggregateState['product_id']);
        $variant->variantId = VariantId::fromString($state['variant_id']);
        $variant->state = VariantState::from($state['state']);
        $variant->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], $state['tax_rate'], $state['includes_vat']);
        $variant->salePrice = VariantSalePrice::fromScalars($state['sale_price'], $state['tax_rate'], $state['includes_vat']);
        $variant->sku = $state['sku'];
        $variant->show_in_grid = $state['show_in_grid'] ? (bool) $state['show_in_grid'] : false;
        $variant->data = json_decode($state['data'], true);

        $variant->optionValueIds = array_map(fn ($optionValueState) => OptionValueId::fromString($optionValueState), $state['option_value_ids']);

        return $variant;
    }
}
