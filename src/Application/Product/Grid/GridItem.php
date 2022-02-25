<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Application\Product\RendersPrices;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

/**
 * A grid item is constructed of a product variant. If a variant is set to be show_in_grid
 * and it is available for purchase, it will be fetched as a griditem.
 */
class GridItem
{
    use RendersPrices;

    private VariantId $variantId;
    private string $title;

    // Set default on construct...
    private Locale $locale;

    // View Model for grid...

    // data...
    // Localized content

    //  private string $id;
    //    private Collection $products;
    //    private ?array $gridProductIds;
    //    private Collection $taxonomy;
    //    private Options $options;
    //    private array $data;




    // View Model for grid...

    // data...
    // Localized content

    //  private string $id;
    //    private Collection $products;
    //    private ?array $gridProductIds;
    //    private Collection $taxonomy;
    //    private Options $options;
    //    private array $data;

    private function __construct()
    {

    }

    public static function fromMappedData(array $state, Locale $locale): static
    {
        $item = new static();
        $item->locale = $locale;
        $item->variantId = VariantId::fromString($state['variant_id']);
        $item->salePrice = VariantSalePrice::fromScalars($state['sale_price'], 'EUR', $state['tax_rate'], $state['includes_tax']);
        $item->unitPrice = VariantUnitPrice::fromScalars($state['unit_price'], 'EUR', $state['tax_rate'], $state['includes_tax']);

        $item->title = 'ddkdkdk';

        if(is_array($state['data'])) {
            // Maybe do this in repository?
//            $item->title = isset($state['data']['title'])

        }

        // TODO: how te set dafult cuurnenen!!!

        return $item;
    }

    public function url(): string
    {
        return '/catalog/' . $this->variantId->get();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getThumbUrl(): string
    {

    }
}
