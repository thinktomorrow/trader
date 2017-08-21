<?php

use Thinktomorrow\Trader\Catalog\Products\Ports\Persistence\InMemoryProductRepository;
use Thinktomorrow\Trader\Order\Application\AddToCart;

require_once __DIR__.'/../../vendor/autoload.php';

// CREATE DUMMY COLLECTION
$productVariants = [
    new \App\ProductVariant(1, ['name' => 'doo'], \Money\Money::EUR(900), \Thinktomorrow\Trader\Common\Domain\Price\Percentage::fromPercent(21), \Money\Money::EUR(500)),
    new \App\ProductVariant(2, ['name' => 'hello'], \Money\Money::EUR(600), \Thinktomorrow\Trader\Common\Domain\Price\Percentage::fromPercent(6), \Money\Money::EUR(500)),
    new \App\ProductVariant(3, ['name' => null], \Money\Money::EUR(300), \Thinktomorrow\Trader\Common\Domain\Price\Percentage::fromPercent(21)),
];

// Collection of products
$products = [
    new \App\Product('eerste product', $productVariants[0]),
    new \App\Product('Tweede product', $productVariants[1]),
    new \App\Product('Derde product', $productVariants[2]),
];

$productRepository = new InMemoryProductRepository();
foreach ($products as $key => $product) {
    $productRepository->add($product, [$productVariants[$key]]);
}

// POST REQUEST
if (isset($_POST['variant_id'])) {
    $variant_id = $_POST['variant_id'];

    // ADD TO CART
    (new AddToCart(new InMemoryProductRepository()))->handle($variant_id, 1);
}

// GET CART
// ...

?>

<?php include __DIR__.'/_header.php'; ?>
<?php include __DIR__.'/_nav.php'; ?>


</div>
<!-- //Header -->
<!-- Heading -->
<h1 class="w3wthreeheadingaits" style="margin-top:80px;">KLEDIJ</h1>
<!-- //Heading -->

<!-- Men's-Product-Display -->
<div class="wthreeproductdisplay">
    <div id="cbp-pgcontainer" class="cbp-pgcontainer">
        <ul class="cbp-pggrid">

            <?php foreach ($products as $product): ?>

                <li class="wthree aits w3l">
                    <div class="cbp-pgcontent" id="men-8">
                        <div class="cbp-pgitem a3ls">
                            <div class="cbp-pgitem-flip">
                                <img src="/shop/images/1-front.jpg" alt="Groovy Apparel">
                            </div>
                        </div>
                        <ul class="cbp-pgoptions w3l">
                            <li class="cbp-pgoptcart">
                                <form action="/shop/index.php" method="post">
                                    <input type="hidden" name="add" value="1">
                                    <input type="hidden" name="variant_id" value="<?= $product->defaultVariantId() ?>">
                                    <button type="submit" class="w3l-cart pw3l-cart"><i class="fa fa-cart-plus" aria-hidden="true"></i></button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <div class="cbp-pginfo w3layouts">
                            <h3><?= $product->name() ?></h3>
                            <span class="cbp-pgprice"><?= $product->price() ?></span>
                        </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include __DIR__.'/_footer.php'; ?>
