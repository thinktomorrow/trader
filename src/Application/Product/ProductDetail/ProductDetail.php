<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\RendersPrices;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class ProductDetail
{
    use RendersPrices;
    use RendersData;

    private VariantId $variantId;
    private array $data;

    private Locale $locale;

    private function __construct()
    {

    }

    public static function fromMappedData(array $state): static
    {
        $item = new static();

        $item->variantId = VariantId::fromString($state['variant_id']);
        $item->salePrice = VariantSalePrice::fromScalars($state['sale_price'], 'EUR', $state['tax_rate'], $state['includes_tax']);
        $item->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], 'EUR', $state['tax_rate'], $state['includes_tax']);
        $item->data = $state['data'];

        return $item;
    }

    public function getId(): string
    {
        return $this->variantId->get();
    }

    public function getTitle(): string
    {
        return $this->data('title');
    }

    public function getThumbUrl(): string
    {

    }

//    public function getOtherProducts(): array
//    {
//
//    }

    public function getImages(): array
    {

    }
}
