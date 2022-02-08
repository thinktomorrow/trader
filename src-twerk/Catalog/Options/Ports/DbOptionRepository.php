<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Ports;

use Illuminate\Database\Eloquent\Collection;
use Thinktomorrow\Trader\Catalog\Options\Domain\Option as OptionContract;
use Thinktomorrow\Trader\Catalog\Options\Domain\OptionRepository;

class DbOptionRepository implements OptionRepository
{
    /** @var Collection */
    private static $optionTypes;

    public function get(string $productGroupId): Options
    {
        $models = OptionModel::where('productgroup_id', $productGroupId)->get();

        return new Options(...$models->map(fn ($model) => static::compose($model))->all());
    }

    public function create(array $values): OptionContract
    {
        $model = OptionModel::create($values);

        return static::compose($model);
    }

    public function save(string $productGroupOptionId, array $values): void
    {
        OptionModel::find($productGroupOptionId)->update($values);
    }

    public function delete(string $productGroupOptionId): void
    {
        // TODO: should take into account existing connections of the products? normally done via db constraints
        OptionModel::find($productGroupOptionId)->delete();
    }

    public static function compose(OptionModel $model): OptionContract
    {
        return new Option(
            (string) $model->id,
            (string) $model->productgroup_id,
            (string) $model->option_type_id,
            $model->values,
            static::optionTypeLabels($model->option_type_id)
        );
    }

    private static function optionTypeLabels($optionTypeId): array
    {
        return static::optionTypes()
            ->first(fn ($optionType) => $optionType->id == $optionTypeId)
            ->dynamic('label') ?: [];
    }

    private static function optionTypes(): Collection
    {
        if (static::$optionTypes) {
            return static::$optionTypes;
        }

        return static::$optionTypes = OptionTypeModel::all();
    }
}
