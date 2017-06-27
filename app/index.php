<?php

use App\Discounts\PercentageOffDiscount;
use App\Product;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Price\Percentage;

require_once __DIR__.'/../vendor/autoload.php';

    $order = new \Thinktomorrow\Trader\Order\Domain\Order(\Thinktomorrow\Trader\Order\Domain\OrderId::fromInteger(1));

    $order->items()->add(
        \Thinktomorrow\Trader\Order\Domain\Item::fromPurchasable(new Product(33,[
                'name' => 'crazy product number one',
                'description' => 'this is a nice looking product guys!!!'
            ],
            \Money\Money::EUR(222),
            Percentage::fromPercent(21)
        )),
        3
    );

$order->items()->add(
    \Thinktomorrow\Trader\Order\Domain\Item::fromPurchasable(new Product(12,[
        'name' => 'awesome product number two',
        'description' => 'in promo! buy second to get 50% off of both'
    ],
        \Money\Money::EUR(3000),
        Percentage::fromPercent(6)
    )),
    (isset($_GET['q2']) ? $_GET['q2'] : 1)
);

    // Add coupon
    $percentageOffDiscount = new PercentageOffDiscount(DiscountId::fromInteger(2), Percentage::fromPercent(20));
    $percentageOffItemDiscount = new \App\Discounts\PercentageOffItemDiscount(DiscountId::fromInteger(32),Percentage::fromPercent(10), new \Thinktomorrow\Trader\Discounts\Domain\DiscountConditions([]));

    (new \Thinktomorrow\Trader\Discounts\Application\ApplyDiscountsToOrder())->handle(
        $order,
        new \Thinktomorrow\Trader\Discounts\Domain\DiscountCollection($percentageOffDiscount, $percentageOffItemDiscount)
    );

    $cart = new \Thinktomorrow\Trader\Order\Cart($order);

?>

<!doctype html>
<html class="no-js" lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>
<body>

<div class="container">

    <h1>Winkelmandje</h1>

    <p>aantal items in mandje: <?= $cart->size() ?></p>

    <table id="cart" class="table table-hover table-condensed">
        <thead>
        <tr>
            <th style="width:50%">Product</th>
            <th style="width:10%">Price</th>
            <th style="width:8%">Quantity</th>
            <th style="width:22%" class="text-center">Subtotal</th>
            <th style="width:10%"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($cart->items() as $item): ?>
            <tr>
                <td data-th="Product">
                    <div class="row">
                        <div class="col-sm-2 hidden-xs"><img src="http://placehold.it/100x100" alt="..." class="img-responsive"/></div>
                        <div class="col-sm-10">
                            <h4 class="nomargin"><?= $item->name() ?></h4>
                            <p><?= $item->description() ?></p>
                            <?php if($item->discounts()->any()): ?>
                                <p>ER ZIJN KORTINGEN</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td data-th="Price"><?= $item->price() ?></td>
                <td data-th="Quantity">
                    <input type="number" class="form-control text-center" value="<?= $item->quantity() ?>">
                </td>
                <td data-th="Subtotal" class="text-center"><?= $item->subtotal() ?></td>
                <td data-th="total" class="text-center"><?= $item->total() ?></td>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
        <tfoot>
        <tr>
            <td colspan="3"></td>
            <td class="text-center"><strong>Subtotal <?= $cart->subtotal() ?></strong></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td class="text-center">
                <?php foreach($cart->discounts() as $discount): ?>
                    <p style="color:red;"><?= $discount->description() ?></p>
                <?php endforeach; ?>
            </td>
            <td></td>
        </tr>
        <tr>
            <td><a href="#" class="btn btn-warning"><i class="fa fa-angle-left"></i> Continue Shopping</a></td>
            <td colspan="2" class="hidden-xs"></td>
            <td class="text-center"><strong>Total <?= $cart->total() ?></strong></td>
            <td><a href="#" class="btn btn-success btn-block">Checkout <i class="fa fa-angle-right"></i></a></td>
        </tr>
        </tfoot>
    </table>
</div>


<script src="https://code.jquery.com/jquery-3.2.0.min.js" integrity="sha256-JAW99MJVpJBGcbzEuXk4Az05s/XyDdBomFqNlM3ic+I=" crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="js/vendor/jquery-3.2.0.min.js"><\/script>')</script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
    // js goes here
</script>
</body>
</html>