<?php

class Payslip{
    private $sal;

    public function __construct(int $slry) {
        $this->sal = $slry;
    }

    public function getNet(){
        $ltbg = Math.max(Math.min($this->sal, 20000.0) - 5000, 0.0);
        $mtbg = Math.max(Math.min($this->sal, 40000) - 20000, 0.0);
        $utbg = Math.max($this->sal - 40000, 0.0);
        return $this->sal - ($ltbg * 0.1 + $mtbg * 0.2 + $utbg * 0.4);
    }

}

public class PayslipTest {
@Test
    public void taxIsZeroIfGrossIsBelowTaxFreeLimit() {
        Payslip payslip = new Payslip(5000);
        assertEquals(5000, payslip.getNet(), 1e-6);
    }


}
