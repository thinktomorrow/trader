<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Illuminate\Support\Str;
use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersVariantPrices;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class DefaultProductDetail implements ProductDetail
{
    use RendersVariantPrices;
    use RendersData;
    use HasLocale;

    private VariantId $variantId;
    private ProductId $productId;
    private VariantState $state;
    private array $taxon_ids;
    private array $data;
    private iterable $images;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state): static
    {
        $item = new static();

        $item->variantId = VariantId::fromString($state['variant_id']);
        $item->productId = ProductId::fromString($state['product_id']);
        $item->state = VariantState::from($state['state']);
        $item->taxon_ids = $state['taxon_ids'];
        $item->salePrice = VariantSalePrice::fromScalars($state['sale_price'], $state['tax_rate'], $state['includes_vat']);
        $item->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], $state['tax_rate'], $state['includes_vat']);
        $item->data = array_merge(
            json_decode($state['product_data'], true),
            json_decode($state['data'], true),
        );

        return $item;
    }

    public function getVariantId(): string
    {
        return $this->variantId->get();
    }

    public function getProductId(): string
    {
        return $this->productId->get();
    }

    public function getTaxonIds(): array
    {
        return $this->taxon_ids;
    }

    public function isAvailable(): bool
    {
        return in_array($this->state, VariantState::availableStates());
    }

    public function getTitle(): string
    {
        if(is_array($this->data('title'))) {
            trap($this->data);
        }

        return $this->data(
            'title',
            null,
            $this->data('product_title', null, '')
        );
    }

    public function getIntro(): string
    {
        return Str::limit($this->getContent(), 160, '...');
    }

    public function getContent(): string
    {
        return $this->data('content', null, '');
    }

    public function getSku(): string
    {
        return $this->getVariantId();
    }

    public function getUrl(): string
    {
        return '/'.$this->getVariantId();
    }

    public function getThumbUrl(): string
    {
    }

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }
}
