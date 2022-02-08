<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Ports;

use Illuminate\Database\Eloquent\Model;

class OptionModel extends Model
{
    public $table = 'trader_options';
    public $guarded = [];
    public $timestamps = false;
    public $casts = [
        'values' => 'array',
    ];

    public function shopOption()
    {
        return $this->belongsTo(OptionTypeModel::class, 'option_type_id');
    }
}
