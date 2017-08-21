<?php require __DIR__.'/../../vendor/autoload.php'; ?>

<?php
$orderRepository = new App\Order\OrderRepository();
$orders = $orderRepository->all();
?>


<?php include __DIR__.'/_header.php'; ?>


<h3>Te behandelen bestellingen</h3>
<table class="table table-bordered">
    <thead>
        <tr>
            <td>Ref.</td>
            <td>Klant</td>
            <td>datum</td>
            <td>status</td>
            <td>bedrag</td>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><a href="/admin/order.php"><?= $order->reference ?></a></td>
            <td>klant</td>
            <td><?= $order->confirmedAt ?></td>
            <td><?= $order->stateBadge ?></td>
            <td><?= $order->total ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__.'/_footer.php'; ?>
