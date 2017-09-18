<?php

namespace Thinktomorrow\Trader\Tax\Application;

use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;
use Thinktomorrow\Trader\Tax\Domain\OrderTaxRate;
use Thinktomorrow\Trader\Tax\Domain\TaxRateRepository;

class ApplyTaxRatesToOrder
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var TaxRateRepository
     */
    private $taxRateRepository;

    public function __construct(OrderRepository $orderRepository, TaxRateRepository $taxRateRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->taxRateRepository = $taxRateRepository;
    }

    public function handle(OrderId $orderId)
    {
        $order = $this->orderRepository->find($orderId);

        foreach ($order->items() as $item) {
            $taxRate = $this->taxRateRepository->find($item->taxId());

            $orderTaxRate = new OrderTaxRate($taxRate, $order);

            $item->setTaxRate($orderTaxRate->get());
        }

        // RESOURCES:
        // https://www.unizo.be/advies/wat-zijn-de-btw-regels-voor-webshops-voor-verkoop-van-en-naar-het-buitenland en
        // https://ec.europa.eu/taxation_customs/sites/taxation/files/resources/documents/taxation/vat/traders/vat_community/vat_in_ec_annexi.pdf

        //Er zijn twee scenario's waarbij de BTW automatisch wordt verlegd naar 0%:
        //Wanneer een bedrijf binnen de EU met een geldig BTW nummer in uw webshop besteld.
        //Wanneer consumenten buiten de EU een bestelling plaatsen (hieronder vallen ook Noorwegen en Zwitserland)
        //Let daarbij op dat dit enkel geldt voor producten waarnaar het product wordt toegestuurd, dus het 'verzend' land dient buiten de EU te vallen of binnen de EU met een geldig BTW nummer.

        // NOTE: each element should already have a TaxId reference!!!!!
        // So what does this class do???? Maybe alter the prices for non-VAT purchases???

        // MerchantOrder->isBusiness() and order->

        // get the billing address of the order, if given

        // Handle each item cause these can have different taxes

        // handle shipment cost cause this is also taxable

        // handle discounts as well
    }
}
