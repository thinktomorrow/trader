<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantLinks\ProductOptionValues;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;

class ProductOptionValuesTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_compose_a_simple_option_array_for_select_field_rendering()
    {
        $repo = new InMemoryProductRepository();

        $product = $this->createProductWithOptions();
        $repo->save($product);

        $values = (new ProductOptionValues($repo))->get($product->productId->get());

        $this->assertCount(2, $values);
        $this->assertEquals([
            [
                'option_id' => 'ooo',
                'data' => ['foo' => 'bar'],
                'values' => [
                    [
                        'option_value_id' => 'xxx',
                        'data' => [
                            'label' => [
                                'nl' => 'option label nl 1',
                                'en' => 'option label en 1',
                            ],
                            'value' => [
                                'nl' => 'option value nl 1',
                                'en' => 'option value en 1',
                            ],
                        ],
                    ],
                    [
                        'option_value_id' => 'yyy',
                        'data' => [
                            'label' => [
                                'nl' => 'option label nl 2',
                                'en' => 'option label en 2',
                            ],
                            'value' => [
                                'nl' => 'option value nl 2',
                                'en' => 'option value en 2',
                            ],
                        ],

                    ],
                ],
            ],
            [
                'option_id' => 'ppp',
                'data' => ['foo' => 'baz'],
                'values' => [
                    [
                        'option_value_id' => 'zzz',
                        'data' => [
                            'label' => [
                                'nl' => 'option label nl 3',
                                'en' => 'option label en 3',
                            ],
                            'value' => [
                                'nl' => 'option value nl 3',
                                'en' => 'option value en 3',
                            ],
                        ],

                    ],
                ],
            ],
        ], $values);
    }
}
