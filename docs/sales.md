# Sales

To allow a product to accept sales, you need to implement the `EligibleForSale` interface. 
This requires the following methods on the object:
```php
interface EligibleForSale
{
    public function price(): Money;

    public function salePrice(): Money;

    public function saleTotal(): Money;

    public function addToSaleTotal(Money $addition);

    public function sales(): array;

    public function addSale(AppliedSale $sale);
}
```

