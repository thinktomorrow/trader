<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariantOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\VariantAlreadyExistsOnProduct;

trait HasVariants
{
    /** @var Variant[] */
    private array $variants = [];

    /** @return Variant[] */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function createVariant(Variant $variant): void
    {
        Assertion::true($variant->productId->equals($this->productId), 'Variant has a different product id ['.$variant->productId->get().'] that the product it is being added to ['.$this->productId->get().'].');

        if (null !== $this->findVariantIndex($variant->variantId)) {
            throw new VariantAlreadyExistsOnProduct(
                'Cannot add variant because product ['.$this->productId->get().'] already has variant ['.$variant->variantId->get().']'
            );
        }

        $this->recordEvent(new VariantCreated($variant->productId, $variant->variantId));

        $this->variants[] = $variant;
    }

    public function updateVariant(Variant $variant): void
    {
        Assertion::true($variant->productId->equals($this->productId), 'Variant has a different product id ['.$variant->productId->get().'] that the product it is being added to ['.$this->productId->get().'].');

        if (null === $variantIndex = $this->findVariantIndex($variant->variantId)) {
            throw new CouldNotFindVariantOnProduct('No variant by id ' . $variant->variantId->get(). ' found on product ' . $this->productId->get());
        }

        $this->variants[$variantIndex] = $variant;

        $this->recordEvent(new VariantUpdated($this->productId, $variant->variantId));
    }

    // TODO: move to variant... and rename this to updateVariant()
//    public function updateVariantPrice(VariantId $variantId, VariantUnitPrice $unitPrice, VariantSalePrice $salePrice): void
//    {
//        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
//            throw new CouldNotFindVariantOnProduct(
//                'Cannot update variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
//            );
//        }
//
//        $this->variants[$variantIndex]->updatePrice($unitPrice, $salePrice);
//
//        $this->recordEvent(new VariantUpdated($this->productId, $variantId));
//    }

    public function deleteVariant(VariantId $variantId): void
    {
        if (null !== $variantIndex = $this->findVariantIndex($variantId)) {
            unset($this->variants[$variantIndex]);

            $this->recordEvent(new VariantDeleted($this->productId, $variantId));
        }
    }

//    public function addVariantOptionValue(VariantId $variantId, OptionId $optionId, OptionValueId $optionValueId): void
//    {
//        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
//            throw new CouldNotFindVariantOnProduct(
//                'Cannot add option selection to variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
//            );
//        }
//
//        if (null !== $this->findOptionIndex($optionId)) {
//            throw new CouldNotFindOptionOnProduct(
//                'Cannot add option ['.$optionId->get().'] to variant because product ['.$this->productId->get().'] does not have this option.'
//            );
//        }
//
//        $this->variants[$variantIndex]->addOrUpdateOption($optionId, $optionValueId);
//    }
//
//    public function updateVariantOptionValue(VariantId $variantId, OptionId $optionId, OptionValueId $optionValueId): void
//    {
//        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
//            throw new CouldNotFindVariantOnProduct(
//                'Cannot add option selection to variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
//            );
//        }
//
//        // TODO CHeck if this option is one of the product ones
////        if (null !== $this->findOptionIndex($option->optionId)) {
////            throw new OptionDoesNotExistOnProduct(
////                'Cannot add option ['.$option->optionId->get().'] because product ['.$this->productId->get().'] already has a variant with option combination.'
////            );
////        }
////
////        // TODO: check uniqueness of option combo
////        if (null !== $this->findOptionIndex($option->optionId)) {
////            throw new OptionCombinationAlreadyExists(
////                'Cannot add option ['.$option->optionId->get().'] because product ['.$this->productId->get().'] already has a variant with option combination.'
////            );
////        }
//
//        $this->variants[$variantIndex]->addOrUpdateOption($optionId, $optionValueId);
//    }
//
//    public function deleteVariantOption(VariantId $variantId, OptionId $optionId): void
//    {
//        if (null === $variantIndex = $this->findVariantIndex($variantId)) {
//            throw new CouldNotFindVariantOnProduct(
//                'Cannot add option selection to variant because product ['.$this->productId->get().'] has no variant by id ['.$variantId->get().']'
//            );
//        }
//
//        $this->variants[$variantIndex]->deleteOption($optionId);
//    }

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
