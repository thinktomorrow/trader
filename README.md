# trader
Please note that this package is still under development. 

Although you are free to use and explore it, the main purpose of trader is to act as the engine for our own ecommerce projects.


## TODO large segments
- Sales
- Payment
- Customer

## TODO
- SumOfTaxes moet nog discounts opnemen. anders wordt tax berekend op een te hoog bedrag.
- AlmostApplicableDiscounts
- DiscountConditions: Should default be only one item to be discounted? (see getAffectedItemQuantity)
- TaxId connection for shipment, payment, discounts. These must be changable as well
- ApplyDiscountsToOrder should accept OrderId, not MerchantOrder. (e.g. ApplySHipmentRuleToOrder)
- taxrate regels duidelijk bepalen: 
// RESOURCES:
// https://www.unizo.be/advies/wat-zijn-de-btw-regels-voor-webshops-voor-verkoop-van-en-naar-het-buitenland en
// https://ec.europa.eu/taxation_customs/sites/taxation/files/resources/documents/taxation/vat/traders/vat_community/vat_in_ec_annexi.pdf
                                            
- shipment costs: ZONES (LANDEN), METHODS
            //  -> RULES: conditions (zone, minimale subtotaal van bestelling, maximale subtotaal van bestelling, ...)
                          costs: baseCost (global for order)
                // RULES SHOULD BE ORDERED AS FIRST TRUE WILL BE USED!

## Shipping rules
- order of rules matter: first one matching order will be chosen
- order of conditions does not matter. Only if all conditions match, the rule will be selected.

## rules
rules have conditions and adjusters attached to them
use rule logic for shipment, payment, tax and discount adjustments on the order
abstract this code

! IMPORTANT FOR DISCOUNTS: volgorde van discounts in discountCollection is van belang. als parameter bij applyDiscountsToOrder handler.
Want subtotaal van order wordt in principe aangepast door de item discounts waardoor het beter is om eerst item gerelateerde discounts toe te passen en dan pas order discounts.
Deze laatste zijn immers afhankelijk van een juiste subtotaal.

## adjusters
allow to add / override adjusters
allow to add / override conditions

## Cart

Cart: available singleton
-> MerchantOrder
-> Customer

application layer: alle acties + events + saves

cart
- orderId (for cookie and db)
- orderStatus (new = cart)
- shipmentMethodId
- paymentMethodId
- customerId
- customerEmail (guest)
- items: itemCollection
    - item:
        purchasableId
        quantity
        adjusters Adjuster[]
- total: Money
- adjusters Adjuster[]

- persistence: key in cookie and data in db
- legal and illegal items: purchasable->isPurchasable()
- assign cart to registered user if possible (user can see own basket when logging in: you have a cart, would you like to retrieve it?)
- same as order
- lineitem adjusters:
    - discount per item
    - saleprice per item (product)
    - tax (business)
- order / cart adjusters
    - discount 
    - couponcode
    - free shipment
    - tax (business)
- adjusters are order specific
- adjusters can be inclusive (default exclusive)
- adjusters need also presenter info (aka line info: label + price) e.g. VAT excl. 12,00

- shipmentMethodId shipment method selection
- paymentMethodId payment method selection
- address info (billing and shipment)
- shipment same as billing flag
- customerId / customerEmail
