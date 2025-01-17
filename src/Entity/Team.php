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

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Parthenon\Billing\Entity\CustomerInterface;
use Parthenon\Billing\Entity\Subscription;
use Parthenon\Common\Address;
use Parthenon\User\Entity\UserInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="teams")
 */
class Team extends \Parthenon\User\Entity\Team implements CustomerInterface
{
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="team")
     *
     * @var UserInterface[]|Collection
     */
    protected Collection $members;

    /**
     * @ORM\Embedded(class="Parthenon\Common\Address")
     */
    protected Address $billingAddress;

    /**
     * @ORM\Embedded(class="Parthenon\Billing\Entity\Subscription")
     */
    private ?Subscription $subscription;

    public function setSubscription(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    public function hasActiveSubscription(): bool
    {
        if (!$this->subscription) {
            return false;
        }

        return $this->subscription->isActive();
    }

    public function getIdentifier(): string
    {
        return (string) $this->getName();
    }

    public function hasSubscription(): bool
    {
        return isset($this->subscription);
    }

    public function setBillingAddress(Address $address)
    {
        $this->billingAddress = $address;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function hasBillingAddress(): bool
    {
        return isset($this->billingAddress);
    }
}
