<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotDeleteVariant;
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
        Assertion::true($variant->productId->equals($this->productId), 'Variant has a different product id [' . $variant->productId->get() . '] that the product it is being added to [' . $this->productId->get() . '].');

        if (null !== $this->findVariantIndex($variant->variantId)) {
            throw new VariantAlreadyExistsOnProduct(
                'Cannot add variant because product [' . $this->productId->get() . '] already has variant [' . $variant->variantId->get() . ']'
            );
        }

        $this->recordEvent(new VariantCreated($variant->productId, $variant->variantId));

        $this->variants[] = $variant;
    }

    public function findVariant(VariantId $variantId): Variant
    {
        $index = $this->findVariantIndex($variantId);

        if (null === $index) {
            throw new CouldNotFindVariantOnProduct(
                'Cannot find variant [' . $variantId->get() . '] on product [' . $this->productId->get() . ']'
            );
        }

        return $this->variants[$index];
    }

    public function updateVariant(Variant $variant): void
    {
        Assertion::true($variant->productId->equals($this->productId), 'Variant has a different product id [' . $variant->productId->get() . '] that the product it is being added to [' . $this->productId->get() . '].');

        if (null === $variantIndex = $this->findVariantIndex($variant->variantId)) {
            throw new CouldNotFindVariantOnProduct('No variant by id ' . $variant->variantId->get() . ' found on product ' . $this->productId->get());
        }

        $this->variants[$variantIndex] = $variant;

        $this->recordEvent(new VariantUpdated($this->productId, $variant->variantId));
    }

    public function deleteVariant(VariantId $variantId): void
    {
        if (null !== $variantIndex = $this->findVariantIndex($variantId)) {
            if (count($this->variants) === 1) {
                throw new CouldNotDeleteVariant('At least one variant is required on a product.');
            }

            unset($this->variants[$variantIndex]);

            $this->recordEvent(new VariantDeleted($this->productId, $variantId));
        }
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
