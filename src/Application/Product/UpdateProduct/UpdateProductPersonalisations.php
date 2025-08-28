<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class UpdateProductPersonalisations
{
    private string $productId;
    private array $personalisations;

    public function __construct(string $productId, array $personalisations)
    {
        $this->productId = $productId;
        $this->personalisations = $personalisations;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    /**
     * @return UpdateProductPersonalisationItem[]
     */
    public function getPersonalisations(): array
    {
        return array_map(function ($personalisation) {
            if (
                ! array_key_exists('personalisation_type', $personalisation) ||
                ! array_key_exists('data', $personalisation)
            ) {
                throw new \InvalidArgumentException('Invalid payload. Personalisation payload should contain personalisation_id, personalisation_type and data properties');
            }

            return new UpdateProductPersonalisationItem(
                $personalisation['personalisation_id'] ?? null,
                $personalisation['personalisation_type'],
                $personalisation['data']
            );
        }, $this->personalisations);
    }
}
