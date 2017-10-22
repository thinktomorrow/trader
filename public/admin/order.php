<?php

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrderFactory;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\InMemoryOrderRepository;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

require __DIR__.'/../../vendor/autoload.php';

// FAKE ADDITION OF ORDER
$confirmedOrder = new Thinktomorrow\Trader\Orders\Domain\Order(\Thinktomorrow\Trader\Orders\Domain\OrderId::fromInteger(1));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Cash::make(50), Percentage::fromPercent(21))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Cash::make(50), Percentage::fromPercent(21))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(2, [], Cash::make(50), Percentage::fromPercent(6))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Cash::make(50), Percentage::fromPercent(21))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(7, [], Cash::make(50), Percentage::fromPercent(9))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(12, [], Money::EUR(50), Percentage::fromPercent(21))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(6, [], Cash::make(1050), Percentage::fromPercent(21))));
$confirmedOrder->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Cash::make(50), Percentage::fromPercent(21))));
$confirmedOrder->setShippingTotal(Cash::make(15));
$confirmedOrder->setPaymentTotal(Cash::make(10));
$confirmedOrder->setTaxPercentage(Percentage::fromPercent(21));
// Add Discount TODO: 1) persist discount, 2) return it from repo without recalculation
$discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off', [], ['percentage' => Percentage::fromPercent(30)]);
$discount->apply($confirmedOrder);

$repo = new InMemoryOrderRepository();
$repo->add($confirmedOrder);

// FETCH ORDER FOR MERCHANT
$order = (new MerchantOrderFactory(new InMemoryOrderRepository()))->create(1);

?>


<?php include __DIR__.'/_header.php'; ?>

<ol class="breadcrumb">
    <li><a href="/admin/orders.php">Orders</a></li>
    <li class="active"><?= $order->reference ?></li>
</ol>

<div class="row">

    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">Behandeling</div>
            <div class="panel-body">
                <h5>Betaling</h5>
                -

                <h5>Verzending</h5>
                -
            </div>
        </div>
    </div>

    <div class="col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">Status</div>
            <div class="panel-body">
                <strong>STATUS</strong><br>
                <?= $order->stateBadge() ?>
            </div>
        </div>
    </div>

</div>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Klant</div>
            <div class="panel-body">
                adresgegevens, mailtjes, ...
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">Bestelling</div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>item</th>
                            <th>voorraad</th>
                            <th>bedrag</th>
                            <th>BTW</th>
                            <th align="right">totaal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($order->items as $item): ?>
                        <tr>
                            <td><?= $item->sku ?> <?= $item->name ?></td>
                            <td><?= $item->stockBadge ?></td>
                            <td><?= $item->quantity ?> x <?= $item->saleprice ?></td>
                            <td><?= $item->taxRate ?></td>
                            <td align="right"><?= $item->total ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td align="right" colspan="4">Subtotaal:</td>
                        <td align="right"><?= $order->subtotal ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="4">Toegepaste kortingen:</td>
                        <td align="right">
                            <?php foreach ($order->discounts() as $appliedDiscount): ?>
                                <?= $appliedDiscount->description ?>
                                <?= $appliedDiscount->amount ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" colspan="4">Betaalkosten (provider):</td>
                        <td align="right"><?= $order->paymentTotal ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="4">Verzendkosten (provider):</td>
                        <td align="right"><?= $order->shippingTotal ?></td>
                    </tr>
                    <?php foreach ($order->taxRates() as $taxRate): ?>

                        <tr>
                            <td align="right" colspan="4">Btw (<?= $taxRate['percent'] ?>):</td>
                            <td align="right"><?= $taxRate['tax'] ?> (<?= $taxRate['total'] ?>)</td>
                        </tr>

                    <?php endforeach; ?>
                    <tr>
                        <td align="right" colspan="4">Btw TOTAL:</td>
                        <td align="right"><?= $order->tax ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="4">Totaal:</td>
                        <td align="right"><?= $order->total ?></td>
                    </tr>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/_footer.php'; ?>
