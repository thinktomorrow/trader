<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Personalisations;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;

class PersonalisationFieldsComposer
{
    private ProductRepository $productRepository;
    private ContainerInterface $container;

    public function __construct(ProductRepository $productRepository, ContainerInterface $container)
    {
        $this->productRepository = $productRepository;
        $this->container = $container;
    }

    public function get(ProductId $productId, Locale $locale): array
    {
        $product = $this->productRepository->find($productId);

        $results = [];

        foreach ($product->getPersonalisations() as $personalisation) {
            $personalisationField = $this->container->get(PersonalisationField::class)::from($personalisation);
            $personalisationField->setLocale($locale);

            $results[] = $personalisationField;
        }

        return $results;
    }
}
