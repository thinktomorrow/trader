<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionValuesUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;

trait HasOptions
{
    private array $options = [];

    public function getNextOptionId(): OptionId
    {
        $i = mt_rand(1,999);
        $nextOptionId = OptionId::fromString($this->productId->get() . '_' . $i);

        while($this->hasOption($nextOptionId)) {
            $nextOptionId = OptionId::fromString($this->productId->get() . '_' . ++$i);
        }

        return $nextOptionId;
    }

    public function updateOptions(array $options): void
    {
        Assertion::allIsInstanceOf($options, Option::class);

        foreach($options as $option) {
            $this->options[$option->optionId->get()] = $option;
        }

        $this->recordEvent(new OptionsUpdated($this->productId));
    }

    public function assignOptionValueToVariant(OptionValueId $optionValueId, VariantId $variantId): void
    {
        /** @var Option $option */
        foreach($this->options as $option) {
            if($option->hasOptionValue($optionValueId)) {
                $optionValue = $option->findOptionValue($optionValueId)->addToVariant($variantId);
                $option->updateOptionValue($optionValue);
            }
        }
    }

    private function hasOption(OptionId $optionId): bool
    {
        /** @var Option $option */
        foreach($this->options as $option) {
            if($option->optionId->equals($optionId)) {
                return true;
            }
        }

        return false;
    }

    public function updateOptionValues(OptionId $optionId, array $optionValues): void
    {
        if (!$this->hasOption($optionId)) {
            throw new CouldNotFindOptionOnProduct(
                'Cannot update option because product ['.$this->productId->get().'] has no option by id ['.$optionId->get().']'
            );
        }

        $this->options[$optionId->get()]->updateOptionValues($optionValues);

        $this->recordEvent(new OptionValuesUpdated($this->productId, $optionId));
    }
}
