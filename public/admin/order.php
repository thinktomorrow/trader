<?php

use Thinktomorrow\Trader\Order\Application\OrderAssembler;

require __DIR__.'/../../vendor/autoload.php'; ?>

<?php

$order = (new OrderAssembler())->forMerchant(1);

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
                            <th align="right">totaal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($order->items as $item): ?>
                        <tr>
                            <td><?= $item->sku ?> <?= $item->name ?></td>
                            <td><?= $item->stockBadge ?></td>
                            <td><?= $item->quantity ?> x <?= $item->saleprice ?></td>
                            <td align="right"><?= $item->total ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td align="right" colspan="3">Subtotaal:</td>
                        <td align="right"><?= $order->subtotal ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Betaalkosten (provider):</td>
                        <td align="right"><?= $order->paymentTotal ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Verzendkosten (provider):</td>
                        <td align="right"><?= $order->shipmentTotal ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Btw (percentage):</td>
                        <td align="right"><?= $order->tax ?></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">Totaal:</td>
                        <td align="right"><?= $order->total ?></td>
                    </tr>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/_footer.php'; ?>
