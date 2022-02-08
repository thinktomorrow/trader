<?php declare(strict_types=1);

namespace Find\Catalog\Projection;

use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Infrastructure\Basic\Find\Catalog\Reads\Product;
use Thinktomorrow\Trader\Infrastructure\Basic\Find\Catalog\Reads\ProductRecordComposer;
use function Thinktomorrow\Trader\Infrastructure\Basic\Find\Catalog\Reads\event;

class ProductProjection
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function replaceOrAdd()
    {
        $composer = $this->container->make(ProductRecordComposer::class);

        $composer->compose();
    }

    private function projectProduct(Product $product)
    {
        $values = $this->toPersistableArray($product);

        $existingSlugs = $this->existingSlugs($product->id);

        ! $this->productReadRepository->exists($product->id)
            ? $this->productReadRepository->create($values)
            : $this->productReadRepository->update($product->id, $values);

        if($this->withEvent){

            // TEMP LOG to check why elastic is failing on some of the indexes.
            // Looks like sometimes no valid id is given...
            // @bugsnag: https://app.bugsnag.com/think-tomorrow/optiphar-shop/errors/5d5f2902aad1a80019f377a2?event_id=5d5f290d004dbf81aa7a0000&i=sk&m=fq
            if(!$product->id) {
                throw new \InvalidArgumentException('No productid passed! ' . print_r($product->toArray(), true));
            }

            event( new ProductProjected($product->id) );

            $translations = (array) json_decode($values['translations']);
            foreach($translations as $locale => $translation){
                if(isset($existingSlugs[$locale]) && $existingSlugs[$locale] != $translation->slug){
                    event(new ProductSlugChanged($product->id, $existingSlugs[$locale], $translation->slug, $locale));
                }
            }
        }
    }
}
