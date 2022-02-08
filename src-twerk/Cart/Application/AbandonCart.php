<?php

declare(strict_types = 1);

namespace Thinktomorrow\Trader\Cart\Application;

use App\Shop\Order\OrderStateMachine;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Cart\Domain\Events\CartAbandoned;
use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Ports\OrderModel;

class AbandonCart
{
    public function handle(OrderReference $orderReference)
    {
        DB::beginTransaction();

        try {

            // TODO: or should this be the order entity and then save it via repository?
            // e.g. $this->repo->findByReference()
            $order = OrderModel::findByReference($orderReference);

            $machine = app()->make(\Thinktomorrow\Trader\Order\Domain\OrderStateMachine::class, ['object' => $order]);
            $machine->apply('abandon');

            trap($order->getState(OrderStateMachine::$KEY));

            // $this->repo->save($order);
            $order->update([OrderStateMachine::$KEY => $order->getState(OrderStateMachine::$KEY)]);

            event(new CartAbandoned($orderReference));

            DB::commit();

            return;
        } catch (StateException $e) {
            // exception is thrown if state transfer is already done
        } catch (\Exception $e) {
            DB::rollback();

            throw $e;
        }
    }
}
