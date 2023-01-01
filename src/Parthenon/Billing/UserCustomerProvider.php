<?php

declare(strict_types=1);

/*
 * Copyright Iain Cambridge 2020-2023.
 *
 * Use of this software is governed by the Business Source License included in the LICENSE file and at https://getparthenon.com/docs/next/license.
 *
 * Change Date: TBD ( 3 years after 2.2.0 release )
 *
 * On the date above, in accordance with the Business Source License, use of this software will be governed by the open source license specified in the LICENSE file.
 */

namespace Parthenon\Billing;

use Parthenon\Billing\Entity\CustomerInterface;
use Parthenon\Billing\Exception\NoCustomerException;
use Symfony\Bundle\SecurityBundle\Security;

final class UserCustomerProvider implements CustomerProviderInterface
{
    public function __construct(private Security $security)
    {
    }

    public function getCurrentCustomer(): CustomerInterface
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new NoCustomerException('No user found');
        }

        if (!$user instanceof CustomerInterface) {
            throw new NoCustomerException('User is not a customer');
        }

        return $user;
    }
}