<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionValuesUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;

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

        // Remove current options.
        $this->options = [];

        $this->options = $options;

        $this->recordEvent(new OptionsUpdated($this->productId));
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
