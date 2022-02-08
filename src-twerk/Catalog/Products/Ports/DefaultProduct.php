<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Thinktomorrow\Trader\Common\Domain\Context;
use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Money\Money;
use Thinktomorrow\Trader\Catalog\Options\Domain\Options;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Common\Domain\HasDataAttribute;
use Thinktomorrow\Trader\Taxes\TaxRate;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup as ProductGroupContract;

class DefaultProduct implements Product
{
    use RendersMoney;
    use HasDataAttribute;

    private string $id;
    private string $productGroupId;
    private bool $isGridProduct;
    private Money $salePrice;
    private Money $unitPrice;
    private TaxRate $taxRate;
    private Options $options;
    protected array $data;

    public function __construct(string $id, bool $isGridProduct, string $productGroupId, Money $salePrice, Money $unitPrice, TaxRate $taxRate, Options $options, array $data)
    {
        $this->id = $id;
        $this->productGroupId = $productGroupId;
        $this->isGridProduct = $isGridProduct;
        $this->salePrice = $salePrice;
        $this->unitPrice = $unitPrice;
        $this->taxRate = $taxRate;
        $this->options = $options;
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSku(): ?string
    {
        return $this->data('sku');
    }

    public function getProductGroupId(): string
    {
        return $this->productGroupId;
    }

    public function isAvailable(): bool
    {
        return in_array($this->data('available_state'), DefaultProductStateMachine::getAvailableStates());
    }

    public function getUrl(): string
    {
        return route('products.show', [$this->getId(), Str::slug($this->getTitle())]);
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function hasOption(string $optionId): bool
    {
        return in_array($optionId, $this->options->getIds());
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTotal(): Money
    {
        return $this->salePrice;
    }

    public function getDiscountTotal(): Money
    {
        return $this->getUnitPrice()->subtract($this->salePrice);
    }

    public function hasDiscount(): bool
    {
        return ! $this->getDiscountTotal()->isZero();
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function getTaxTotal(): Money
    {
        $nettTotal = Cash::from($this->getTotal())->subtractTaxPercentage($this->getTaxRate()->toPercentage());

        return $this->getTotal()->subtract($nettTotal);
    }

    public function isTaxApplicable(): bool
    {
        // This can be changed based on context (b2b channel, specific logged in user, toggle on site, ...)
        return true;
    }

    public function doPricesIncludeTax(): bool
    {
        return $this->data('prices_include_tax', true);
    }

    public function isGridProduct(): bool
    {
        return $this->isGridProduct;
    }

    public function getTaxableTotal(): Money
    {
        return $this->getTotal();
    }

    public function getTitle(): string
    {
        return $this->data('title', '');
    }

    public function getImages(): Collection
    {
        return collect($this->data('images', []));
    }

    public function getSpecs(): ?string
    {
        return $this->data('specs');
    }

    public function getTotalAsString(): string
    {
        return str_replace(',00', '', $this->renderMoney($this->getTotal(), Context::current()->getLocale()));
    }

    public function getUnitPriceAsString(): string
    {
        // Remove any 00 decimals in the view
        return str_replace(',00', '', $this->renderMoney($this->getUnitPrice(), Context::current()->getLocale()));
    }

    public function getMergedImages(ProductGroupContract $productGroup): Collection
    {
        return $this->getImages()
            ->merge($productGroup->getImages())

            // Avoid duplicates in the merge
            ->reject(function ($asset) {
                static $loadedAssetIds = [];

                if (in_array($asset->id, $loadedAssetIds)) {
                    return true;
                }

                $loadedAssetIds[] = $asset->id;

                return false;
            });
    }
}
