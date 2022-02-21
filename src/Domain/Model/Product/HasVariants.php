<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantAdded;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariantOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\VariantAlreadyExistsOnProduct;

trait HasVariants
{
    private array $variants = [];

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function addVariant(Variant $variant): void
    {
        Assertion::true($variant->productId->equals($this->productId), 'Variant has a different product id ['.$variant->productId->get().'] that the product it is being added to ['.$this->productId->get().'].');

        if (null !== $this->findVariantIndex($variant->variantId)) {
            throw new VariantAlreadyExistsOnProduct(
                'Cannot add variant because product ['.$this->productId->get().'] already has variant ['.$variant->variantId->get().']'
            );
        }

        $this->variants[] = $variant;

        $this->recordEvent(new VariantAdded($this->productId, $variant->variantId));
    }

    public function updateVariantPrice(VariantId $variantId, VariantUnitPrice $unitPrice, VariantSalePrice $salePrice): void
    {
        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
            throw new CouldNotFindVariantOnProduct(
                'Cannot update variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
            );
        }

        $this->variants[$variantIndex]->updatePrice($unitPrice, $salePrice);

        $this->recordEvent(new VariantUpdated($this->productId, $variantId));
    }

    public function deleteVariant(VariantId $variantId): void
    {
        if (null !== $variantIndex = $this->findVariantIndex($variantId)) {
            unset($this->variants[$variantIndex]);

            $this->recordEvent(new VariantDeleted($this->productId, $variantId));
        }
    }

    public function addVariantOptionValue(VariantId $variantId, OptionId $optionId, OptionValueId $optionValueId): void
    {
        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
            throw new CouldNotFindVariantOnProduct(
                'Cannot add option selection to variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
            );
        }

        if (null !== $this->findOptionIndex($optionId)) {
            throw new CouldNotFindOptionOnProduct(
                'Cannot add option ['.$optionId->get().'] to variant because product ['.$this->productId->get().'] does not have this option.'
            );
        }

        $this->variants[$variantIndex]->addOrUpdateOption($optionId, $optionValueId);
    }

    public function updateVariantOptionValue(VariantId $variantId, OptionId $optionId, OptionValueId $optionValueId): void
    {
        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
            throw new CouldNotFindVariantOnProduct(
                'Cannot add option selection to variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
            );
        }

        // TODO CHeck if this option is one of the product ones
//        if (null !== $this->findOptionIndex($option->optionId)) {
//            throw new OptionDoesNotExistOnProduct(
//                'Cannot add option ['.$option->optionId->get().'] because product ['.$this->productId->get().'] already has a variant with option combination.'
//            );
//        }
//
//        // TODO: check uniqueness of option combo
//        if (null !== $this->findOptionIndex($option->optionId)) {
//            throw new OptionCombinationAlreadyExists(
//                'Cannot add option ['.$option->optionId->get().'] because product ['.$this->productId->get().'] already has a variant with option combination.'
//            );
//        }

        $this->variants[$variantIndex]->addOrUpdateOption($optionId, $optionValueId);
    }

    public function deleteVariantOption(VariantId $variantId, OptionId $optionId): void
    {
        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
            throw new CouldNotFindVariantOnProduct(
                'Cannot add option selection to variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
            );
        }

        $this->variants[$variantIndex]->deleteOption($optionId);
    }

    private function findVariantIndex(VariantId $variantId): ?int
    {
        foreach ($this->variants as $index => $variant) {
            if ($variantId->equals($variant->variantId)) {
                return $index;
            }
        }

        return null;
    }
}
