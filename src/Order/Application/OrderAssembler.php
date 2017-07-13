<?php

namespace Thinktomorrow\Trader\Order\Application;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Order\Ports\Web\Merchant\Order as MerchantOrder;

class OrderAssembler
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function forMerchant($orderId)
    {
        $data = $this->orderRepository->getValuesForMerchantOrder(OrderId::fromInteger($orderId));
        $items = $this->orderRepository->getItemsForMerchantOrder(OrderId::fromInteger($orderId));

        // This data (raw) will then be transposed to a simple read-only DTO

        // Get raw data from specific query
        return new MerchantOrder([
            'total' => Money::EUR(1290),
            'subtotal' => Money::EUR(900),
            'payment_total' => Money::EUR(0),
            'shipment_total' => Money::EUR(50),
            'tax' => Money::EUR(30),
            'tax_rate' => Percentage::fromPercent(21),
            'reference' => 'a782820ZIsksa',
            'confirmed_at' => (new \DateTime('@'.strtotime('-9days'))),
            'state' => 'confirmed',
            'items' => [
                [
                    'name' => 'dude',
                    'sku' => '123490',
                    'stock' => 5,
                    'stock_warning' => false,
                    'saleprice' => Money::EUR(120),
                    'quantity' => 2,
                    'total' => Money::EUR(240),
                ],
                [
                    'name' => 'tweede',
                    'sku' => '1293939',
                    'stock' => 1,
                    'stock_warning' => true,
                    'saleprice' => Money::EUR(820),
                    'total' => Money::EUR(820),
                ],
            ],
        ]);
    }
}