<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ListGridProducts;

use Thinktomorrow\Trader\Domain\Common\Locale;

class GridVariant
{
    // View Model for grid...

    // data...
    // Localized content

    //  private string $id;
    //    private Collection $products;
    //    private ?array $gridProductIds;
    //    private Collection $taxonomy;
    //    private Options $options;
    //    private array $data;


    public function __construct()
    {

    }

    // Set default on construct...
    private Locale $locale;

    public function setLocale(Locale $locale): void
    {

    }

    public function url(): string
    {
        // Use locale to localize url...
    }

    public function getTitle(): string
    {

    }

    public function getSalePrice(): string
    {

    }

    public function getUnitPrice(): string
    {

    }

    public function isOnSale(): bool
    {

    }

    public function getThumbUrl(): string
    {

    }
}
