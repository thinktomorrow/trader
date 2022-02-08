<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Ports;

use App\ShopAdmin\Catalog\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Thinktomorrow\DynamicAttributes\HasDynamicAttributes;

class OptionTypeModel extends Model
{
    use HasDynamicAttributes;
    use HasUniqueSlug;

    public $table = 'trader_option_types';
    public $guarded = [];

    public array $dynamicKeys = [
        'internal_label', 'label',
    ];

    private function getSlugBaseAttribute(): string
    {
        return $this->internal_label;
    }

    private function getSlugAttributeKey(): string
    {
        return 'key';
    }

    private function getSlugEvents(): array
    {
        return ['creating'];
    }
}
