<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Promo\CUD\DeletePromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoDeleted;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;

class DeletePromoTest extends PromoContext
{
    use TestHelpers;

    public function test_it_can_delete_a_promo()
    {
        $promo = $this->createPromo([], [
            $this->createDiscount([], [$this->createCondition()]),
        ]);
        $this->promoRepository->save($promo);

        $this->promoApplication->deletePromo(new DeletePromo($promo->promoId->get()));

        $this->assertEquals([
            new PromoDeleted($promo->promoId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindPromo::class);
        $this->promoRepository->find($promo->promoId);
    }
}
