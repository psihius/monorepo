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

namespace Parthenon\Billing\Plan\Security\Voter;

use Parthenon\Billing\CustomerProviderInterface;
use Parthenon\Billing\Entity\CustomerInterface;
use Parthenon\Billing\Entity\Subscription;
use Parthenon\Billing\Plan\Counter\TeamInviteCounterInterface;
use Parthenon\Billing\Plan\LimitedUserInterface;
use Parthenon\Billing\Plan\Plan;
use Parthenon\Billing\Plan\PlanManagerInterface;
use Parthenon\Common\Address;
use Parthenon\User\Entity\TeamInviteCode;
use Parthenon\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class InviteVoterTest extends TestCase
{
    public function testDeniesIfNotLoggedIn()
    {
        $token = $this->createMock(TokenInterface::class);
        $counter = $this->createMock(TeamInviteCounterInterface::class);
        $planManager = $this->createMock(PlanManagerInterface::class);
        $teamInviteCode = new TeamInviteCode();
        $customerProvider = $this->createMock(CustomerProviderInterface::class);

        $token->method('getUser')->willReturn(null);

        $voter = new InviteVoter($counter, $planManager, $customerProvider);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $teamInviteCode, ['create']));
    }

    public function testDeniesIfOverLimitFromUserCount()
    {
        $token = $this->createMock(TokenInterface::class);
        $counter = $this->createMock(TeamInviteCounterInterface::class);
        $planManager = $this->createMock(PlanManagerInterface::class);
        $limitable = new TeamInviteCode();
        $plan = $this->createMock(Plan::class);
        $currentSubscriberProvider = $this->createMock(CustomerProviderInterface::class);

        $subscriber = new class() implements CustomerInterface {
            public function setSubscription(Subscription $subscription)
            {
                // TODO: Implement setSubscription() method.
            }

            public function getSubscription(): Subscription
            {
                // TODO: Implement getSubscription() method.
            }

            public function hasActiveSubscription(): bool
            {
                return true;
            }

            public function getIdentifier(): string
            {
                // TODO: Implement getIdentifier() method.
            }

            public function hasSubscription(): bool
            {
                // TODO: Implement hasSubscription() method.
            }

            public function setBillingAddress(Address $address)
            {
                // TODO: Implement setBillingAddress() method.
            }

            public function getBillingAddress(): Address
            {
                // TODO: Implement getBillingAddress() method.
            }

            public function hasBillingAddress(): bool
            {
                // TODO: Implement hasBillingAddress() method.
            }
        };
        $currentSubscriberProvider->method('getCurrentCustomer')->willReturn($subscriber);

        $member = new class() extends User implements LimitedUserInterface {
            public function getPlanName(): string
            {
                return 'plan';
            }
        };

        $token->method('getUser')->willReturn($member);

        $planManager->method('getPlanForUser')->with($member)->willReturn($plan);
        $plan->method('isPerSeat')->with($limitable)->willReturn(false);
        $plan->method('getUserCount')->with($limitable)->willReturn(1);
        $counter->method('getCount')->with($member)->willReturn(4);

        $voter = new InviteVoter($counter, $planManager, $currentSubscriberProvider);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $limitable, ['create']));
    }

    public function testDeniesIfOverLimitFromPerSeat()
    {
        $token = $this->createMock(TokenInterface::class);
        $counter = $this->createMock(TeamInviteCounterInterface::class);
        $planManager = $this->createMock(PlanManagerInterface::class);
        $limitable = new TeamInviteCode();
        $plan = $this->createMock(Plan::class);
        $currentSubscriberProvider = $this->createMock(CustomerProviderInterface::class);

        $subscriber = new class() implements CustomerInterface {
            public function setSubscription(Subscription $subscription)
            {
                // TODO: Implement setSubscription() method.
            }

            public function getSubscription(): Subscription
            {
                $subscription = new Subscription();
                $subscription->setSeats(1);

                return $subscription;
            }

            public function hasActiveSubscription(): bool
            {
                return true;
            }

            public function getIdentifier(): string
            {
                // TODO: Implement getIdentifier() method.
            }

            public function hasSubscription(): bool
            {
                // TODO: Implement hasSubscription() method.
            }

            public function setBillingAddress(Address $address)
            {
                // TODO: Implement setBillingAddress() method.
            }

            public function getBillingAddress(): Address
            {
                // TODO: Implement getBillingAddress() method.
            }

            public function hasBillingAddress(): bool
            {
                // TODO: Implement hasBillingAddress() method.
            }
        };
        $currentSubscriberProvider->method('getCurrentCustomer')->willReturn($subscriber);

        $member = new class() extends User implements LimitedUserInterface {
            public function getPlanName(): string
            {
                return 'plan';
            }
        };

        $token->method('getUser')->willReturn($member);

        $planManager->method('getPlanForUser')->with($member)->willReturn($plan);
        $plan->method('isPerSeat')->with($limitable)->willReturn(true);
        $plan->method('getUserCount')->with($limitable)->willReturn(10);
        $counter->method('getCount')->with($member)->willReturn(4);

        $voter = new InviteVoter($counter, $planManager, $currentSubscriberProvider);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $limitable, ['create']));
    }

    public function testAllowsFromPerSeat()
    {
        $token = $this->createMock(TokenInterface::class);
        $counter = $this->createMock(TeamInviteCounterInterface::class);
        $planManager = $this->createMock(PlanManagerInterface::class);
        $limitable = new TeamInviteCode();
        $plan = $this->createMock(Plan::class);
        $currentSubscriberProvider = $this->createMock(CustomerProviderInterface::class);

        $subscriber = new class() implements CustomerInterface {
            public function setSubscription(Subscription $subscription)
            {
                // TODO: Implement setSubscription() method.
            }

            public function getSubscription(): Subscription
            {
                $subscription = new Subscription();
                $subscription->setSeats(10);

                return $subscription;
            }

            public function hasActiveSubscription(): bool
            {
                return true;
            }

            public function getIdentifier(): string
            {
                // TODO: Implement getIdentifier() method.
            }

            public function hasSubscription(): bool
            {
                // TODO: Implement hasSubscription() method.
            }

            public function setBillingAddress(Address $address)
            {
                // TODO: Implement setBillingAddress() method.
            }

            public function getBillingAddress(): Address
            {
                // TODO: Implement getBillingAddress() method.
            }

            public function hasBillingAddress(): bool
            {
                // TODO: Implement hasBillingAddress() method.
            }
        };
        $currentSubscriberProvider->method('getCurrentCustomer')->willReturn($subscriber);

        $member = new class() extends User implements LimitedUserInterface {
            public function getPlanName(): string
            {
                return 'plan';
            }
        };

        $token->method('getUser')->willReturn($member);

        $planManager->method('getPlanForUser')->with($member)->willReturn($plan);
        $plan->method('isPerSeat')->with($limitable)->willReturn(true);
        $plan->method('getUserCount')->with($limitable)->willReturn(1);
        $counter->method('getCount')->with($member)->willReturn(4);

        $voter = new InviteVoter($counter, $planManager, $currentSubscriberProvider);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $limitable, ['create']));
    }

    public function testAllowsFromUserCount()
    {
        $token = $this->createMock(TokenInterface::class);
        $counter = $this->createMock(TeamInviteCounterInterface::class);
        $planManager = $this->createMock(PlanManagerInterface::class);
        $limitable = new TeamInviteCode();
        $plan = $this->createMock(Plan::class);
        $currentSubscriberProvider = $this->createMock(CustomerProviderInterface::class);

        $subscriber = new class() implements CustomerInterface {
            public function setSubscription(Subscription $subscription)
            {
                // TODO: Implement setSubscription() method.
            }

            public function getSubscription(): Subscription
            {
                $subscription = new Subscription();
                $subscription->setSeats(1);

                return $subscription;
            }

            public function hasActiveSubscription(): bool
            {
                return true;
            }

            public function getIdentifier(): string
            {
                // TODO: Implement getIdentifier() method.
            }

            public function hasSubscription(): bool
            {
                // TODO: Implement hasSubscription() method.
            }

            public function setBillingAddress(Address $address)
            {
                // TODO: Implement setBillingAddress() method.
            }

            public function getBillingAddress(): Address
            {
                // TODO: Implement getBillingAddress() method.
            }

            public function hasBillingAddress(): bool
            {
                // TODO: Implement hasBillingAddress() method.
            }
        };
        $currentSubscriberProvider->method('getCurrentCustomer')->willReturn($subscriber);

        $member = new class() extends User implements LimitedUserInterface {
            public function getPlanName(): string
            {
                return 'plan';
            }
        };

        $token->method('getUser')->willReturn($member);

        $planManager->method('getPlanForUser')->with($member)->willReturn($plan);
        $plan->method('isPerSeat')->with($limitable)->willReturn(false);
        $plan->method('getUserCount')->with($limitable)->willReturn(10);
        $counter->method('getCount')->with($member)->willReturn(4);

        $voter = new InviteVoter($counter, $planManager, $currentSubscriberProvider);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $limitable, ['create']));
    }
}
