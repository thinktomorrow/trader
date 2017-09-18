<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Merchant;

interface MerchantOrderResource
{
    public function merchantValues(): array;

    public function merchantItemValues(): array;

    public function merchantDiscountValues(): array;
}