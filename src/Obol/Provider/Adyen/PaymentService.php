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

namespace Obol\Provider\Adyen;

use Brick\Money\Money;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Obol\Model\BillingDetails;
use Obol\Model\CardOnFileResponse;
use Obol\Model\Charge;
use Obol\Model\ChargeCardResponse;
use Obol\Model\Subscription;
use Obol\Model\SubscriptionCreationResponse;
use Obol\PaymentServiceInterface;
use Obol\Provider\Adyen\DataMapper\PaymentDetailsMapper;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class PaymentService implements PaymentServiceInterface
{
    use CustomerReferenceTrait;

    private const TEST_BASE_URL = 'https://checkout-test.adyen.com/v69/payments';
    private const LIVE_BASE_URL = 'https://%s-checkout-live.adyenpayments.com/checkout/v69/payments';

    private const TEST_DISABLE_URL = 'https://pal-test.adyen.com/pal/servlet/Recurring/v68/disable';
    private const LIVE_DISABLE_URL = 'https://%s-pal-live.adyenpayments.com/pal/servlet/Recurring/v68/disable';
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private PaymentDetailsMapper $paymentDetailsMapper;
    private string $baseUrl;
    private string $disableUrl;

    public function __construct(
        private Config $config,
        ?PaymentDetailsMapper $paymentDetailsMapper = null,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->paymentDetailsMapper = $paymentDetailsMapper ?? new PaymentDetailsMapper();
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->baseUrl = $this->config->isTestMode() ? self::TEST_BASE_URL : sprintf(self::LIVE_BASE_URL, $this->config->getPrefix());
        $this->disableUrl = $this->config->isTestMode() ? self::TEST_DISABLE_URL : sprintf(self::LIVE_DISABLE_URL, $this->config->getPrefix());
    }

    public function startSubscription(Subscription $subscription): SubscriptionCreationResponse
    {
        if (!$subscription->getBillingDetails()->hasCustomerReference()) {
            $this->setCustomerReference($subscription->getBillingDetails());
        }

        $payload = $this->paymentDetailsMapper->subscriptionPayload($subscription, $this->config);

        $request = $this->createApiRequest('POST', $this->baseUrl);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($payload)));

        $response = $this->client->sendRequest($request);

        $jsonData = json_decode($response->getBody()->getContents(), true);

        if (200 === $response->getStatusCode()) {
            $paymentDetails = $this->paymentDetailsMapper->buildPaymentDetails($jsonData);
            $paymentDetails->setAmount($subscription->getTotalCost());

            $subscriptionCreationResponse = new SubscriptionCreationResponse();
            $subscriptionCreationResponse->setPaymentDetails($paymentDetails)
                ->setSubscriptionId($jsonData['pspReference']);

            return $subscriptionCreationResponse;
        }

        if (401 === $response->getStatusCode()) {
            throw new \Exception('Unauthorized request - most likely an invalid API key');
        }

        if (403 === $response->getStatusCode()) {
            throw new \Exception('Forbidden - most likely invalid roles. Check if you have PCI rights');
        }

        throw new \Exception('Unable to make request');
    }

    public function stopSubscription(Subscription $subscription): void
    {
        $this->deleteCardFile($subscription->getBillingDetails());
    }

    public function createCardOnFile(BillingDetails $billingDetails): CardOnFileResponse
    {
        if (!$billingDetails->hasCustomerReference()) {
            $this->setCustomerReference($billingDetails);
        }

        $payload = $this->paymentDetailsMapper->addCardToFilePayload($billingDetails, $this->config);

        $request = $this->createApiRequest('POST', $this->baseUrl);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($payload)));

        $response = $this->client->sendRequest($request);

        $jsonData = json_decode($response->getBody()->getContents(), true);

        if (200 === $response->getStatusCode()) {
            $paymentDetails = $this->paymentDetailsMapper->buildPaymentDetails($jsonData);
            $paymentDetails->setAmount(Money::of(0, 'USD'));
            $cardOnFile = new CardOnFileResponse();
            $cardOnFile->setPaymentDetails($paymentDetails);

            return $cardOnFile;
        }

        if (401 === $response->getStatusCode()) {
            throw new \Exception('Unauthorized request - most likely an invalid API key');
        }

        if (403 === $response->getStatusCode()) {
            throw new \Exception('Forbidden - most likely invalid roles. Check if you have PCI rights');
        }

        throw new \Exception('Unable to make request');
    }

    public function deleteCardFile(BillingDetails $billingDetails): void
    {
        if (!$billingDetails->usePrestoredCard()) {
            throw new \Exception('No card data to delete');
        }

        $payload = $this->paymentDetailsMapper->addCardToFilePayload($billingDetails, $this->config);

        $request = $this->createApiRequest('POST', $this->disableUrl);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($payload)));

        $response = $this->client->sendRequest($request);

        $jsonData = json_decode($response->getBody()->getContents(), true);

        if (200 === $response->getStatusCode()) {
            return;
        }

        if (401 === $response->getStatusCode()) {
            throw new \Exception('Unauthorized request - most likely an invalid API key');
        }

        if (403 === $response->getStatusCode()) {
            throw new \Exception('Forbidden - most likely invalid roles. Check if you have PCI rights');
        }

        throw new \Exception('Unable to make request');
    }

    public function chargeCardOnFile(Charge $charge): ChargeCardResponse
    {
        if (!$charge->getBillingDetails()->usePrestoredCard()) {
            throw new \Exception('No card data to delete');
        }

        $payload = $this->paymentDetailsMapper->chargeCardPayload($charge, $this->config);

        $request = $this->createApiRequest('POST', $this->baseUrl);
        $request = $request->withBody($this->streamFactory->createStream(json_encode($payload)));

        $response = $this->client->sendRequest($request);

        $jsonData = json_decode($response->getBody()->getContents(), true);

        if (200 === $response->getStatusCode()) {
            $paymentDetails = $this->paymentDetailsMapper->buildPaymentDetails($jsonData);
            $paymentDetails->setAmount($charge->getAmount());

            $cardOnFile = new ChargeCardResponse();
            $cardOnFile->setPaymentDetails($paymentDetails);

            return $cardOnFile;
        }

        throw new \Exception('Unable to make request');
    }

    protected function createApiRequest(string $method, string $url): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $url);

        return $request->withAddedHeader('x-API-key', $this->config->getApiKey())->withAddedHeader('Content-Type', 'application/json');
    }
}
