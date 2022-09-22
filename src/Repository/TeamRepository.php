<?php

declare(strict_types=1);

/*
 * Copyright Humbly Arrogant Ltd 2020-2022.
 *
 * Use of this software is governed by the Business Source License included in the LICENSE file and at https://getparthenon.com/docs/next/license.
 *
 * Change Date: TBD ( 3 years after 2.0.0 release )
 *
 * On the date above, in accordance with the Business Source License, use of this software will be governed by the open source license specified in the LICENSE file.
 */

namespace App\Repository;

use App\Entity\User;
use Parthenon\Subscriptions\SubscriptionInterface;
use Parthenon\User\Entity\UserInterface;

class TeamRepository extends \Parthenon\User\Repository\TeamRepository implements TeamRepositoryInterface
{
    /**
     * @param User $user
     */
    public function getSubscriptionForUser(UserInterface $user): SubscriptionInterface
    {
        return $user->getTeam()->getSubscription();
    }

    public function findAllSubscriptions(): array
    {
        return $this->entityRepository->createQueryBuilder('t')
            ->where('t.subscription.planName is not null')
            ->getQuery()
            ->getResult();
    }
}
