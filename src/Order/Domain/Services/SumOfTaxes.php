<?php

namespace Thinktomorrow\Trader\Order\Domain\Services;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Order\Domain\Order;

class SumOfTaxes
{
    public function forOrder(Order $order)
    {
        // TODO: take discounts into account
        $totalsPerRate = $this->mergeTotals($this->itemTotalsPerRate($order), $this->globalTotalsPerRate($order));
        $totalsPerRate = $this->calculateTax($totalsPerRate);

        return $totalsPerRate;
    }

    /**
     * For each rate the item totals are added up. The tax amount is then calculated for each total.
     * If we would take the tax amount of each item, the tax total is prone to rounding errors.
     *
     * @param Order $order
     *
     * @return array
     */
    private function itemTotalsPerRate(Order $order): array
    {
        $totalsPerRate = [];

        foreach ($order->items() as $item) {
            $key = (string) $item->taxRate()->asPercent();

            if (!isset($totalsPerRate[$key])) {
                $totalsPerRate[$key] = ['percent' => $item->taxRate(), 'total' => Cash::make(0)];
            }

            $totalsPerRate[$key]['total'] = $totalsPerRate[$key]['total']->add($item->total());
        }

        return $totalsPerRate;
    }

    /**
     * Add Shipment and Payment Tax.
     *
     * @param Order $order
     *
     * @return array
     */
    private function globalTotalsPerRate(Order $order): array
    {
        $taxPercentage = $order->taxPercentage();

        if (!$taxPercentage->isPositive()) {
            return [];
        }

        $totalsPerRate = [];
        $key = (string) $taxPercentage->asPercent();

        foreach ([$order->shipmentTotal(), $order->paymentTotal()] as $global) {
            if (!$global->isPositive()) {
                continue;
            }

            if (!isset($totalsPerRate[$key])) {
                $totalsPerRate[$key] = ['percent' => $taxPercentage, 'total' => Cash::make(0)];
            }

            $totalsPerRate[$key]['total'] = $totalsPerRate[$key]['total']->add($global);
        }

        return $totalsPerRate;
    }

    /**
     * @param $main
     * @param $second
     *
     * @return array
     */
    private function mergeTotals($main, $second): array
    {
        foreach ($second as $key => $totalPerRate) {
            if (!isset($main[$key])) {
                $main[$key] = $totalPerRate;
                continue;
            }

            $main[$key]['total'] = $main[$key]['total']->add($totalPerRate['total']);
        }

        return $main;
    }

    /**
     * @param $totalsPerRate
     *
     * @return mixed
     */
    private function calculateTax($totalsPerRate)
    {
        foreach ($totalsPerRate as $key => $totalPerRate) {
            $totalsPerRate[$key]['tax'] = $totalPerRate['total']->multiply($totalPerRate['percent']->asFloat());
        }

        return $totalsPerRate;
    }
}
