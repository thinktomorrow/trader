<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductAdded;

class Product implements Aggregate
{
    use RecordsEvents;
    use HasOptions;
    use HasVariants;
    use HasData;

    public readonly ProductId $productId;
    private array $taxa;

    // TODO
    private array $options = [
        // Option (optionId,
        // view: list all options with their variant link
    ];

    private function __construct()
    {

    }

    public static function create(ProductId $productId): static
    {
        $product = new static();
        $product->productId = $productId;

        $product->recordEvent(new ProductAdded($product->productId));

        return $product;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Variant::class => array_map(fn(Variant $variant) => $variant->getMappedData(), $this->variants),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $product = new static();
        $product->productId = ProductId::fromString($state['product_id']);

        return $product;
    }
}
