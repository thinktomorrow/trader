<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use App\Shop\Catalog\Products\BulkPrices;
use Thinktomorrow\Trader\Common\State\Stateful;
use Thinktomorrow\Trader\Catalog\Products\Domain\UsingProductState;
use Illuminate\Database\Eloquent\Model;
use Thinktomorrow\AssetLibrary\AssetTrait;
use Thinktomorrow\AssetLibrary\HasAsset;
use Thinktomorrow\DynamicAttributes\HasDynamicAttributes;
use Thinktomorrow\Trader\Catalog\Options\Ports\OptionModel;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Common\Cash\IntegerConverter;
use Thinktomorrow\Trader\Taxes\TaxRate;

class ProductModel extends Model implements HasAsset, Stateful
{
    use HasDynamicAttributes;
    use AssetTrait;
    use UsingProductState;

    public $table = 'trader_products';
    public $guarded = [];

    public $casts = [
        'sale_price' => 'int',
        'unit_price' => 'int',
        'tax_rate' => 'int',
        'bulk_prices' => 'array',
        'is_grid_product' => 'bool',
    ];

    public array $dynamicKeys = [
        'title',
    ];

    public $with = [
        'assetRelation',
        'assetRelation.media',
    ];

    protected function dynamicDocumentKey(): string
    {
        return 'data';
    }

    public function getMorphClass()
    {
        return 'product_model';
    }

    public function doPricesIncludeTax(): bool
    {
        return app('trader_config')->doPricesIncludeTax();
    }

    public function options()
    {
        return $this->belongsToMany(OptionModel::class, 'trader_option_product', 'product_id', 'option_id');
    }

    public function getBulkPrices(): BulkPrices
    {
        $bulkPrices = new BulkPrices(Cash::make($this->sale_price));

        if ($this->bulk_prices) {
            foreach ($this->bulk_prices as $bulk_price) {
                $priceExclusiveTax = (new ProductPrice())->getPriceExclusiveTax(
                    Cash::make(IntegerConverter::convertDecimalToInteger($bulk_price['unit_price'])),
                    TaxRate::fromInteger($this->tax_rate),
                    $this->doPricesIncludeTax()
                );

                $priceInclusiveTax = (new ProductPrice())->getPriceInclusiveTax(
                    Cash::make(IntegerConverter::convertDecimalToInteger($bulk_price['unit_price'])),
                    TaxRate::fromInteger($this->tax_rate),
                    $this->doPricesIncludeTax()
                );

                $bulkPrices->add(
                    (int) $bulk_price['from'],
                    (int) $bulk_price['to'],
                    $priceExclusiveTax,
                    $priceInclusiveTax
                );
            }
        }

        return $bulkPrices;
    }
}
