<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionAdded;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\OptionAlreadyExistsOnProduct;

trait HasOptions
{
    private array $options = [];

    public function addOption(Option $option): void
    {
        // TODO CHeck if this option is one of the product ones
        if (null !== $this->findOptionIndex($option->optionId)) {
            throw new OptionAlreadyExistsOnProduct(
                'Cannot add option ['.$option->optionId->get().'] because product ['.$this->productId->get().'] already has a variant with option combination.'
            );
        }

        $this->options[] = $option;

        $this->recordEvent(new OptionAdded($this->productId, $option->optionId));
    }

    public function updateOptionValueIds(OptionId $optionId, array $optionValueIds): void
    {
        // TODO: check uniqueness of options

        if (null === $optionIndex = $this->findOptionIndex($optionId)) {
            throw new CouldNotFindOptionOnProduct(
                'Cannot update option because product ['.$this->productId->get().'] has no option by id ['.$optionId->get().']'
            );
        }

        $this->options[$optionIndex]->updateOptionValueIds($optionValueIds);

        $this->recordEvent(new OptionUpdated($this->productId, $optionId));
    }

    public function deleteOption(OptionId $optionId): void
    {
        if (null === $optionIndex = $this->findOptionIndex($optionId)) {
            throw new CouldNotFindOptionOnProduct(
                'Cannot delete option because product ['.$this->productId->get().'] has no option by id ['.$optionId->get().']'
            );
        }

        unset($this->options[$optionIndex]);

        $this->recordEvent(new OptionDeleted($this->productId, $optionId));
    }

    private function findOptionIndex(OptionId $optionId): ?int
    {
        foreach ($this->options as $index => $option) {
            if ($optionId->equals($option->optionId)) {
                return $index;
            }
        }

        return null;
    }
}
