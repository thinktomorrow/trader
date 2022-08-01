<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Personalisations;

use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;

class PersonalisationValues
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function get(string $product_id): array
    {
        $product = $this->productRepository->find(ProductId::fromString($product_id));

        return array_map(fn ($personalisation) => $this->convertToArrayItem($personalisation), $product->getPersonalisations());
    }

    private function convertToArrayItem(Personalisation $personalisation): array
    {
        return [
            'personalisation_id' => $personalisation->personalisationId->get(),
            'personalisation_type' => $personalisation->personalisationType->get(),
            'data' => $personalisation->getData(),
        ];
    }
}
