# trader

## TODO
- AlmostApplicableDiscounts
- DiscountConditions: Should default be only one item to be discounted? (see getAffectedItemQuantity)
## Cart

Cart: available singleton
-> Order
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
