<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\CUD;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Events\PromoDeleted;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\TraderConfig;

class PromoApplication
{
    private TraderConfig $traderConfig;
    private EventDispatcher $eventDispatcher;
    private PromoRepository $promoRepository;
    private DiscountFactory $discountFactory;

    public function __construct(TraderConfig $traderConfig, EventDispatcher $eventDispatcher, PromoRepository $promoRepository, DiscountFactory $discountFactory)
    {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->promoRepository = $promoRepository;
        $this->discountFactory = $discountFactory;
    }


    public function createPromo(CreateCouponPromo $command): PromoId
    {
        $promoId = $this->promoRepository->nextReference();

        $promo = Promo::create($promoId, $command->getCouponCode(), $command->getStartAt(), $command->getEndAt(), $command->isCombinable());
        $promo->addData($command->getData());

        $this->promoRepository->save($promo);

        $this->eventDispatcher->dispatchAll($promo->releaseEvents());

        return $promoId;
    }

    public function updatePromo(UpdatePromo $command): void
    {
        $promo = $this->promoRepository->find($command->getPromoId());

        $promo->updateCouponCode($command->getCouponCode());
        $promo->updateStartAt($command->getStartAt());
        $promo->updateEndAt($command->getEndAt());
        $promo->updateIsCombinable($command->isCombinable());
        $promo->addData($command->getData());

        $discounts = [];
        foreach ($command->getDiscounts() as $commandDiscount) {
            $discountId = $commandDiscount->getDiscountId() ?: $this->promoRepository->nextDiscountReference();

            // Conditions ...
            $conditionStates = [];
            foreach ($commandDiscount->getConditions() as $conditionPayload) {
                $conditionStates[] = [
                    'key' => $conditionPayload->getMapKey(),
                    'data' => json_encode($conditionPayload->getData()),
                ];
            }

            $discounts[] = $this->discountFactory->make(
                $commandDiscount->getMapKey(),
                [
                    'promo_id' => $promo->promoId->get(),
                    'discount_id' => $discountId->get(),
                    'data' => json_encode($commandDiscount->getData()),
                ],
                $promo->getMappedData(),
                $conditionStates
            );
        }

        $promo->updateDiscounts($discounts);

        $this->promoRepository->save($promo);

        $this->eventDispatcher->dispatchAll($promo->releaseEvents());
    }

    public function deletePromo(DeletePromo $command): void
    {
        $this->promoRepository->delete($command->getPromoId());

        $this->eventDispatcher->dispatchAll([
            new PromoDeleted($command->getPromoId()),
        ]);
    }
}
