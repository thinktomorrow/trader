<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Adjusters\AdjusterStrategy;

class ProductReadFactory
{
    use AdjusterStrategy;

    protected $adjusters = [

    ];

    public function create(ProductRead $productRead, array $adjusterInstances): ProductRead
    {
        $this->applyAdjusters($productRead, $adjusterInstances);

        return $productRead;
    }

    public function createMany(array $products, array $adjusterInstances): array
    {
        Assertion::allIsInstanceOf($products, ProductRead::class);

        return array_map(function(ProductRead $product) use($adjusterInstances){
            return $this->create($product, $adjusterInstances);
        }, $products);
    }
}
