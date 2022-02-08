<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Taxes;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Cash\Cash;

class TaxRateTotals
{
    private $taxables;

    private function __construct(array $taxables)
    {
        Assertion::allIsInstanceOf($taxables, Taxable::class);

        $this->taxables = $taxables;
    }

    public static function fromTaxables(array $taxables): self
    {
        return new static($taxables);
    }

    public function get(): array
    {
        $totalsByRate = $this->groupTotalsByRate();

        return $this->addTaxToEachRate($totalsByRate);
    }

//    public function forCart(Cart $cart)
//    {
//        $totalsPerRate = $this->mergeTotals(
//            $this->itemTotalsPerRate($cart->items()),
//            $this->globalTotalsPerRate($cart));
//    }

    /**
     * For each rate the item totals are added up. The tax amount is then calculated for each total.
     * If we would take the tax amount of each item, the tax total is prone to rounding errors.
     *
     * @return array
     */
    private function groupTotalsByRate(): array
    {
        $totalsPerRate = [];

        /** @var Taxable $taxable */
        foreach ($this->taxables as $taxable) {
            $key = (string) $taxable->getTaxRate()->toPercentage()->toInteger();

            if (! isset($totalsPerRate[$key])) {
                $totalsPerRate[$key] = ['percent' => $taxable->getTaxRate()->toPercentage(), 'total' => Cash::zero()];
            }

            $totalsPerRate[$key]['total'] = $totalsPerRate[$key]['total']->add($taxable->getTaxableTotal());
        }

        return $totalsPerRate;
    }

    /**
     * Add a 'tax' entry for each tax rate. This represents
     * the tax amount for each rate total.
     *
     * @param array $totalsByRate
     *
     * @return mixed
     */
    private function addTaxToEachRate(array $totalsByRate): array
    {
        foreach ($totalsByRate as $key => $totalByRate) {
            $nettTotal = Cash::from($totalByRate['total'])->subtractTaxPercentage($totalByRate['percent']);
            $totalsByRate[$key]['tax'] = $totalByRate['total']->subtract($nettTotal);
        }

        return $totalsByRate;
    }
}
