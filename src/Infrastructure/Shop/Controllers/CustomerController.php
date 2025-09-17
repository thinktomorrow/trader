<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\Controllers;

class CustomerController
{
    public function __construct()
    {
    }

    public function index()
    {
        return view('chief-trader-shop::customer.index');
    }
}
