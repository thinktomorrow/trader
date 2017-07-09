<?php

namespace Thinktomorrow\Trader\Tax\Application;

use Thinktomorrow\Trader\Order\Domain\OrderId;

class ApplyTaxRatesToOrder
{
    public function __construct()
    {

    }

    public function handle(OrderId $orderId)
    {
//        De standaard eisen van de EU over belastingen hebben wij standaard in ons systeem verwerkt.
//
//    Er zijn twee scenario's waarbij de BTW automatisch wordt verlegd naar 0%:
//
//
//Wanneer een bedrijf binnen de EU met een geldig BTW nummer in uw webshop besteld.
//Wanneer consumenten buiten de EU een bestelling plaatsen (hieronder vallen ook Noorwegen en Zwitserland)
//Let daarbij op dat dit enkel geldt voor producten waarnaar het product wordt toegestuurd, dus het 'verzend' land dient buiten de EU te vallen of binnen de EU met een geldig BTW nummer.
//
        // NOTE: each element should already have a TaxId reference!!!!!
        // So what does this class do???? Maybe alter the prices for non-VAT purchases???

        // Order->isBusiness() and order->

        // get the billing address of the order, if given

        // Handle each item cause these can have different taxes

        // handle shipment cost cause this is also taxable

        // handle discounts as well
    }
}