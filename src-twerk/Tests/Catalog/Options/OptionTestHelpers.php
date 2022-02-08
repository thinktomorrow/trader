<?php

namespace Thinktomorrow\Trader\Tests\Catalog\Options;

use Thinktomorrow\Trader\Catalog\Options\Application\SaveOptions;
use Thinktomorrow\Trader\Catalog\Options\Application\SaveOptionsPayload;
use Thinktomorrow\Trader\Catalog\Options\Ports\OptionTypeModel;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;

trait OptionTestHelpers
{
    private function defaultOptionSetup($productGroupId)
    {
        $optionModel = \Thinktomorrow\Trader\Catalog\Options\Ports\OptionTypeModel::create(['internal_label' => 'color', 'label' => ['nl' => 'kleur', 'en' => 'color']]);
        $optionModel2 = OptionTypeModel::create(['internal_label' => 'taste', 'label' => ['nl' => 'smaak', 'en' => 'taste']]);

        $payload = (new SaveOptionsPayload($productGroupId))
            ->add($optionModel->id, ['nl' => 'blauw', 'en' => 'blue'])
            ->add($optionModel->id, ['nl' => 'groen', 'en' => 'green'])
            ->add($optionModel2->id, ['nl' => 'melk', 'en' => 'milk']);

        app(SaveOptions::class)->handle($payload);
    }

    private function productWithOption(array $values = []): Product
    {
        $optionType = \Thinktomorrow\Trader\Catalog\Options\Ports\OptionTypeModel::create([
            'internal_label' => 'flavor',
        ]);

        $productGroup = $this->createProductGroup();

        app(SaveOptions::class)->handle((new SaveOptionsPayload($productGroup->getId()))->add(
            (string) $optionType->id,
            ['nl' => 'blue'],
        ));

        return $this->createProduct(array_merge([
            'options' => [1],
        ], $values));
    }
}
