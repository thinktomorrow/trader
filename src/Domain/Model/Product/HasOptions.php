<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionValuesUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;

trait HasOptions
{
    /** @var Option[] */
    private array $options = [];

    /** @return Option[] */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getNextOptionId(): OptionId
    {
        $i = mt_rand(1, 999);
        $nextOptionId = OptionId::fromString(substr($i .'_' . $this->productId->get(), 0, 36));

        while ($this->hasOption($nextOptionId)) {
            $nextOptionId = OptionId::fromString(substr(++$i .'_' . $this->productId->get(), 0, 36));
        }

        return $nextOptionId;
    }

    public function updateOptions(array $options): void
    {
        Assertion::allIsInstanceOf($options, Option::class);

        $this->removeOptionValueIdsOnVariants($this->options, $options);

        $this->options = $options;

        $this->recordEvent(new OptionsUpdated($this->productId));
    }

    private function removeOptionValueIdsOnVariants(array $existingOptions, array $newOptions)
    {
        $newOptionIds = array_map(fn (Option $option) => $option->optionId, $newOptions);

        foreach ($existingOptions as $existingOption) {
            if (in_array($existingOption->optionId, $newOptionIds)) {
                continue;
            }

            $removedOptionValueIds = array_map(fn (OptionValue $optionValue) => $optionValue->optionValueId, $existingOption->getOptionValues());

            foreach ($this->getVariants() as $variant) {
                $variantOptionValueIds = $variant->getOptionValueIds();

                foreach ($variantOptionValueIds as $k => $v) {
                    if (in_array($v, $removedOptionValueIds)) {
                        unset($variantOptionValueIds[$k]);
                    }
                }

                if (count($variantOptionValueIds) !== $variant->getOptionValueIds()) {
                    $variant->updateOptionValueIds($variantOptionValueIds);
                }
            }
        }
    }

    public function updateOptionValues(OptionId $optionId, array $optionValues): void
    {
        if (! $this->hasOption($optionId)) {
            throw new CouldNotFindOptionOnProduct(
                'Cannot update option because product ['.$this->productId->get().'] has no option by id ['.$optionId->get().']'
            );
        }

        foreach ($this->options as $i => $existingOption) {
            if ($existingOption->optionId->equals($optionId)) {
                $this->options[$i]->updateOptionValues($optionValues);
                $this->recordEvent(new OptionValuesUpdated($this->productId, $optionId));

                return;
            }
        }
    }

    private function hasOption(OptionId $optionId): bool
    {
        /** @var Option $option */
        foreach ($this->options as $option) {
            if ($option->optionId->equals($optionId)) {
                return true;
            }
        }

        return false;
    }
}
