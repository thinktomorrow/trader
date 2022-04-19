<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Option;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionValue;

class Option implements ChildAggregate
{
    use HasData;

    public readonly ProductId $productId;
    public readonly OptionId $optionId;
    private array $optionValues = [];

    private function __construct()
    {

    }

    public static function create(ProductId $productId, OptionId $optionId, array $data): static
    {
        $option = new static();

        $option->productId = $productId;
        $option->optionId = $optionId;
        $option->data = $data;

        return $option;
    }

    public function getNextOptionValueId(): OptionValueId
    {
        $i = mt_rand(1,999);
        $nextOptionValueId = OptionValueId::fromString(substr($i .'_' . $this->optionId->get(), 0, 36));

        while($this->hasOptionValue($nextOptionValueId)) {
            $nextOptionValueId = OptionValueId::fromString(substr(++$i .'_' . $this->optionId->get(), 0, 36));
        }

        return $nextOptionValueId;
    }

    /** @return OptionValue[] */
    public function getOptionValues(): array
    {
        return $this->optionValues;
    }

    public function hasOptionValue(OptionValueId $optionValueId): bool
    {
        /** @var OptionValue $optionValue */
        foreach($this->optionValues as $optionValue) {
            if($optionValue->optionValueId->equals($optionValueId)) {
                return true;
            }
        }

        return false;
    }

    public function updateOptionValue(OptionValue $optionValue): void
    {
        /** @var OptionValue $optionValue */
        foreach($this->optionValues as $i => $_optionValue) {
            if($_optionValue->optionValueId->equals($optionValue->optionValueId)) {
                $this->optionValues[$i] = $optionValue;
            }
        }
    }

    public function findOptionValue(OptionValueId $optionValueId): OptionValue
    {
        /** @var OptionValue $optionValue */
        foreach($this->optionValues as $i => $_optionValue) {
            if($_optionValue->optionValueId->equals($optionValue->optionValueId)) {
                return $this->optionValues[$i];
            }
        }

        throw new CouldNotFindOptionValue('No option value found by id ' . $optionValueId->get());
    }

    public function updateOptionValues(array $optionValues): void
    {
        Assertion::allIsInstanceOf($optionValues, OptionValue::class);

        foreach($optionValues as $optionValue) {
            if(! $this->optionId->equals($optionValue->optionId)) {
                throw new \InvalidArgumentException('Cannot add option value. You are trying to add an option value with option id [' . $optionValue->optionId->get() . '] to option with id ['.$this->optionId->get().']');
            }
        }

        $this->optionValues = $optionValues;
    }

    public function getChildEntities(): array
    {
        return [
            OptionValue::class => array_map(fn($optionValue) => $optionValue->getMappedData(), $this->optionValues),
        ];
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'option_id' => $this->optionId->get(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $option = new static();

        $option->productId = ProductId::fromString($aggregateState['product_id']);
        $option->optionId = OptionId::fromString($state['option_id']);
        $option->optionValues = array_map(fn($optionValueState) => OptionValue::fromMappedData($optionValueState, $state), $childEntities[OptionValue::class] ?? []);
        $option->data = json_decode($state['data'], true);

        return $option;
    }
}
