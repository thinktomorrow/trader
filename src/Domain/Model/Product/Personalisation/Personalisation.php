<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Personalisation;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class Personalisation implements ChildEntity
{
    use HasData;

    public readonly ProductId $productId;
    public readonly PersonalisationId $personalisationId;
    public readonly PersonalisationType $personalisationType;

    private function __construct()
    {
    }

    public static function create(ProductId $productId, PersonalisationId $personalisationId, PersonalisationType $personalisationType, array $data): static
    {
        $personalisation = new static();

        $personalisation->productId = $productId;
        $personalisation->personalisationId = $personalisationId;
        $personalisation->personalisationType = $personalisationType;
        $personalisation->data = $data;

        return $personalisation;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'personalisation_id' => $this->personalisationId->get(),
            'personalisation_type' => $this->personalisationType->get(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $personalisation = new static();

        $personalisation->productId = ProductId::fromString($aggregateState['product_id']);
        $personalisation->personalisationId = PersonalisationId::fromString($state['personalisation_id']);
        $personalisation->personalisationType = PersonalisationType::fromString($state['personalisation_type']);
        $personalisation->data = $state['data'] ? json_decode($state['data'], true) : [];

        return $personalisation;
    }
}
