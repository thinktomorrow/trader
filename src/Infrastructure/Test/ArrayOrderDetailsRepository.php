<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Line;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Payment;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Discount;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Shipping;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetails;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetailsRepository;

final class ArrayOrderDetailsRepository implements OrderDetailsRepository
{
    private array $orders = [];
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;

    public function __construct(OrderRepository $orderRepository, ProductRepository $productRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    public function find(OrderId $orderId): OrderDetails
    {
        $order = $this->orderRepository->find($orderId);

        return OrderDetails::fromMappedData([
                'order_id' => $order->orderId->get(),
            ], [
                BillingAddress::class => BillingAddress::empty()->toArray(),
                ShippingAddress::class => ShippingAddress::empty()->toArray(),
                Shipping::class => [
                    'id' => 'aaa',
                    'state' => ShippingState::initialized->value,
                    'cost' => 0,
                    'tax_rate' => "10",
                    'includes_vat' => true,
                ],
                Payment::class => [
                    'id' => 'bbb',
                    'state' => PaymentState::initialized->value,
                    'cost' => 0,
                    'tax_rate' => "10",
                    'includes_vat' => true,
                ],
                Line::class => array_map(function($line){

                    $product = $this->productRepository->find($line->getProductId());

                    return [
                        'product_unit_price' => $product->getMappedData()['product_unit_price'],
                        'tax_rate' => $product->getMappedData()['tax_rate'],
                        'includes_vat' => $product->getMappedData()['includes_vat'],
                        'quantity' => $line->getQuantity()->asInt(),
                    ];

                }, $order->getChildEntities()[\Thinktomorrow\Trader\Domain\Model\Order\Entity\Line::class]),
                Discount::class => [],
            ]
        );
    }
}
