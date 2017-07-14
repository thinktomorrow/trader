<?php

namespace Thinktomorrow\Trader\Order\Domain\Services;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;

class SumOfItemTaxes
{
    public function forItems(ItemCollection $items)
    {
        $taxTotalsPerRate = $this->totalsPerRate($items);

        // Get tax from total
        foreach($taxTotalsPerRate as $key => $totalPerRate)
        {
            $taxTotalsPerRate[$key]['tax'] = $totalPerRate['total']->multiply($totalPerRate['percent']->asFloat());
            unset($taxTotalsPerRate[$key]['total']);
        }

        return $taxTotalsPerRate;
    }

    /**
     * @param ItemCollection $items
     * @return array
     */
    private function totalsPerRate(ItemCollection $items): array
    {
        $totalsPerRate = [];

        foreach ($items as $item) {
            $key = (string)$item->taxRate()->asPercent();

            if (!isset($totalsPerRate[$key])) {
                $totalsPerRate[$key] = ['percent' => $item->taxRate(), 'total' => Money::EUR(0)];
            }

            $totalsPerRate[$key]['total'] = $totalsPerRate[$key]['total']->add($item->total());
        }

        return $totalsPerRate;
    }
}