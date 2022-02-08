<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Ports;

use App\ShopAdmin\Catalog\Taxa\TaxonState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Thinktomorrow\AssetLibrary\AssetTrait;
use Thinktomorrow\AssetLibrary\HasAsset;
use Thinktomorrow\DynamicAttributes\HasDynamicAttributes;
use Thinktomorrow\Trader\Common\State\Stateful;

class TaxonModel extends Model implements HasAsset, Stateful
{
    use HasDynamicAttributes;
    use AssetTrait;

    public $table = "trader_taxa";
    public $guarded = [];
    public array $dynamicKeys = [
        'label', 'content', 'show_online',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('sort', function (Builder $builder) {
            $builder->orderBy('order_column', 'ASC');
        });
    }

    public function dynamicLocales(): array
    {
        return app()->make('trader_config')->languages();
    }

    protected function dynamicDocumentKey(): string
    {
        return 'data';
    }

    public static function findByKey(string $key): ?TaxonModel
    {
        return static::where('key', $key)->first();
    }

    public function getMorphClass()
    {
        return 'taxon_model';
    }

    public function getState(string $key): string
    {
        return $this->$key ?? TaxonState::ONLINE;
    }

    public function changeState(string $key, $state): void
    {
        $this->$key = $state;
    }
}
