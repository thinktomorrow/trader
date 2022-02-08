<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Thinktomorrow\Trader\Common\State\Stateful;
use Thinktomorrow\Trader\Catalog\Products\Domain\UsingProductGroupState;
use Illuminate\Database\Eloquent\Model;
use Thinktomorrow\AssetLibrary\AssetTrait;
use Thinktomorrow\AssetLibrary\HasAsset;
use Thinktomorrow\DynamicAttributes\HasDynamicAttributes;
use Thinktomorrow\Trader\Catalog\Options\Ports\OptionModel;
use Thinktomorrow\Trader\Catalog\Taxa\Ports\TaxonModel;

class ProductGroupModel extends Model implements HasAsset, Stateful
{
    use AssetTrait;
    use UsingProductGroupState;

    public $table = 'trader_productgroups';
    public $guarded = [];

    use HasDynamicAttributes;

    public array $dynamicKeys = [
        'title', 'intro', 'content',
    ];

    public $with = [
        'products',
        'options',
        'products.options',
        'taxa',
        'assetRelation.media',
    ];

    public static function findByProductId(string $productId): self
    {
        return static::join('trader_products', 'trader_productgroups.id', '=', 'trader_products.productgroup_id')
            ->where('trader_products.id', $productId)
            ->select('trader_productgroups.*')
            ->first();
    }

    protected function dynamicDocumentKey(): string
    {
        return 'data';
    }

    public function taxa()
    {
        return $this->belongsToMany(TaxonModel::class, 'trader_taxa_products', 'productgroup_id', 'taxon_id')
            ->orderBy('trader_taxa.order_column', 'ASC');
    }

    public function gridProducts()
    {
        return $this->products()->where('is_grid_product', true);
    }

    public function products()
    {
        return $this->hasMany(ProductModel::class, 'productgroup_id')->orderBy('order_column', 'ASC');
    }

    public function options()
    {
        return $this->hasMany(OptionModel::class, 'productgroup_id');
    }

    public function getMorphClass()
    {
        return 'product_group_model';
    }
}
