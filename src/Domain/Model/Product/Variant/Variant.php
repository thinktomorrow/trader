<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\HasVariantTaxa;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

final class Variant implements ChildAggregate
{
    use HasData;
    use HasVariantTaxa;

    public readonly ProductId $productId;
    public readonly VariantId $variantId;
    private VariantState $state;
    private VariantUnitPrice $unitPrice;
    private VariantSalePrice $salePrice; // bedrag, btw perc, bool includes_tax?

    /** @var Personalisation[] */
    private array $personalisations = [];

    private string $sku;
    private ?string $ean = null;
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

    public function updateSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function updateEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    public function showInGrid(bool $show_in_grid = true): void
    {
        $this->show_in_grid = $show_in_grid;
    }

    public function showsInGrid(): bool
    {
        return $this->show_in_grid;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'variant_id' => $this->variantId->get(),
            'state' => $this->state->value,
            'unit_price' => $this->unitPrice->getMoney()->getAmount(),
            'sale_price' => $this->salePrice->getMoney()->getAmount(),
            'tax_rate' => $this->unitPrice->getVatPercentage()->get(),
            'includes_vat' => $this->unitPrice->includesVat(),
            'sku' => $this->sku,
            'ean' => $this->ean,
            'show_in_grid' => $this->show_in_grid,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            VariantTaxon::class => array_map(
                fn (VariantTaxon $option) => array_merge($option->getMappedData()),
                array_values($this->variantTaxa),
            ),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $variant = new static();

        $variant->productId = ProductId::fromString($aggregateState['product_id']);
        $variant->variantId = VariantId::fromString($state['variant_id']);
        $variant->state = VariantState::from($state['state']);
        $variant->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], $state['tax_rate'], $state['includes_vat']);
        $variant->salePrice = VariantSalePrice::fromScalars($state['sale_price'], $state['tax_rate'], $state['includes_vat']);
        $variant->sku = $state['sku'];
        $variant->ean = $state['ean'] ?? null;
        $variant->show_in_grid = $state['show_in_grid'] ? (bool)$state['show_in_grid'] : false;
        $variant->data = json_decode($state['data'], true);

        if (array_key_exists(VariantTaxon::class, $childEntities)) {
            foreach ($childEntities[VariantTaxon::class] as $childState) {
                $variant->variantTaxa[] = (isset($childState['taxonomy_type']) && $childState['taxonomy_type'] == TaxonomyType::variant_property->value)
                    ? \Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::fromMappedData($childState, $state)
                    : VariantTaxon::fromMappedData($childState, $state);
            }
        }

        return $variant;
    }
}
