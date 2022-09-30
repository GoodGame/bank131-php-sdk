<?php

declare(strict_types=1);

namespace Bank131\SDK\Tests\Unit\API;

use Bank131\SDK\API\Request\Session\ChargebackPaymentSessionRequest;
use Bank131\SDK\API\Request\Session\CreateSessionRequest;
use Bank131\SDK\API\Request\Session\InitPaymentSessionRequest;
use Bank131\SDK\API\Request\Session\InitPayoutSessionRequest;
use Bank131\SDK\API\Request\Session\InitPayoutSessionWithFiscalizationRequest;
use Bank131\SDK\API\Request\Session\RefundPaymentSessionRequest;
use Bank131\SDK\API\Request\Session\StartPaymentSessionRequest;
use Bank131\SDK\API\Request\Session\StartPayoutSessionRequest;
use Bank131\SDK\API\Request\Session\StartPayoutSessionRequestWithFiscalization;
use Bank131\SDK\DTO\AcquiringPayment;
use Bank131\SDK\DTO\AcquiringPaymentRefund;
use Bank131\SDK\DTO\FiscalizationService;
use Bank131\SDK\DTO\Payout;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;

class SessionApiTest extends AbstractApiTest
{
    public function testInitPaymentSession(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'in_progress',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http://bank131.ru'
                        ],
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->initPayment(
            $this->createMock(InitPaymentSessionRequest::class)
        );

        $this->assertEquals($status, $sessionResponse->getStatus());

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());
    }

    public function testInitPayoutSession(): void
    {
        $expectedResponseBody = [
            "status"=> "ok",
            "session"=> [
                "id"=> $sessionId = "test_ps_1",
                "status"=> "in_progress",
                "created_at"=> $sessionCreatedAt = "2020-06-08T11:00:03.567464Z",
                "updated_at"=> $sessionUpdatedAt = "2020-06-08T11:10:03.625759Z",
                "payments"=> [
                    [
                        "id"=> $payoutId = "test_po_1",
                        "status"=> $payoutStatus = "in_progress",
                        "created_at"=> $payoutCreatedAt = "2020-06-08T11:10:03.618104Z",
                        "customer"=> [
                            "reference"=> $customerReference = "lucky"
                        ],
                        "payment_method"=> [
                            "type"=> "card",
                                "card"=> [
                                    "brand"=> $cardBrand = "visa",
                                    "last4"=> $cardLast4 = "4242"
                                ]
                            ],
                        "amount_details"=> [
                            "amount"=> $amount = 1000,
                            "currency"=> $currency = "rub"
                        ],
                        "participant_details"=> [
                            "recipient"=> [
                                "full_name"=> $recipientFullName = "John Doe",
                                "document"=> $recipientDocument = "1234567890",
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->initPayout(
            $this->createMock(InitPayoutSessionRequest::class)
        );

        $this->assertTrue($sessionResponse->isOk());

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertTrue($session->isInProgress());
        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getPayments());
        $this->assertCount(1, $session->getPayments());

        /** @var Payout $payout */
        $payout = $session->getPayments()[0];

        $this->assertEquals($payoutId, $payout->getId());
        $this->assertEquals($payoutStatus, $payout->getStatus());
        $this->assertEquals(new DateTimeImmutable($payoutCreatedAt), $payout->getCreatedAt());
        $this->assertEquals($customerReference, $payout->getCustomer()->getReference());
        $this->assertEquals($amount, $payout->getAmountDetails()->getAmount());
        $this->assertEquals($currency, $payout->getAmountDetails()->getCurrency());
        $this->assertEquals($cardBrand, $payout->getPaymentMethod()->getCard()->getBrand());
        $this->assertEquals($cardLast4, $payout->getPaymentMethod()->getCard()->getLast4());
        $this->assertEquals($recipientFullName, $payout->getParticipantDetails()->getRecipient()->getFullName());
        $this->assertEquals($recipientDocument, $payout->getParticipantDetails()->getRecipient()->getDocument());
    }

    public function testInitPayoutSessionWithFiscalization(): void
    {
        $expectedResponseBody = [
            "status"=> "ok",
            "session"=> [
                "id"=> $sessionId = "test_ps_1",
                "status"=> "in_progress",
                "created_at"=> $sessionCreatedAt = "2020-06-08T11:00:03.567464Z",
                "updated_at"=> $sessionUpdatedAt = "2020-06-08T11:10:03.625759Z",
                "payments"=> [
                    [
                        "id"=> $payoutId = "test_po_1",
                        "status"=> $payoutStatus = "in_progress",
                        "created_at"=> $payoutCreatedAt = "2020-06-08T11:10:03.618104Z",
                        "customer"=> [
                            "reference"=> $customerReference = "lucky"
                        ],
                        "payment_method"=> [
                            "type"=> "card",
                            "card"=> [
                                "brand"=> $cardBrand = "visa",
                                "last4"=> $cardLast4 = "4242"
                            ]
                        ],
                        "fiscalization_details"=> [
                            "professional_income_taxpayer"=> [
                                "services" => [
                                    [
                                        "name" => $serviceName = "Test",
                                        "amount_details" => [
                                            "amount" => $serviceAmount = 1000,
                                            "currency" => $serviceCurrency = "rub"
                                        ],
                                        "quantity" => $serviceQuantity = 1
                                    ]
                                ],
                                "tax_reference" => $taxReference = "123456789012",
                                "payer_type" => $payerType = "individual"
                            ]
                        ],
                        "amount_details"=> [
                            "amount"=> $amount = 1000,
                            "currency"=> $currency = "rub"
                        ],
                        "participant_details"=> [
                            "recipient"=> [
                                "full_name"=> $recipientFullName = "John Doe"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->initPayoutWithFiscalization(
            $this->createMock(InitPayoutSessionWithFiscalizationRequest::class)
        );

        $this->assertTrue($sessionResponse->isOk());

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertTrue($session->isInProgress());
        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getPayments());
        $this->assertCount(1, $session->getPayments());

        /** @var Payout $payout */
        $payout = $session->getPayments()[0];

        $this->assertEquals($payoutId, $payout->getId());
        $this->assertEquals($payoutStatus, $payout->getStatus());
        $this->assertEquals(new DateTimeImmutable($payoutCreatedAt), $payout->getCreatedAt());
        $this->assertEquals($customerReference, $payout->getCustomer()->getReference());
        $this->assertEquals($amount, $payout->getAmountDetails()->getAmount());
        $this->assertEquals($currency, $payout->getAmountDetails()->getCurrency());
        $this->assertEquals($cardBrand, $payout->getPaymentMethod()->getCard()->getBrand());
        $this->assertEquals($cardLast4, $payout->getPaymentMethod()->getCard()->getLast4());
        $this->assertEquals($recipientFullName, $payout->getParticipantDetails()->getRecipient()->getFullName());

        $this->assertNotNull($fiscalizationDetails = $payout->getFiscalizationDetails());
        $this->assertNotNull($incomeInformation = $fiscalizationDetails->getProfessionalIncomeTaxpayer());

        $this->assertIsIterable($incomeInformation->getServices());
        $this->assertCount(1, $incomeInformation->getServices());

        /** @var FiscalizationService $service */
        $service = $incomeInformation->getServices()->get(0);

        $this->assertEquals($serviceAmount, $service->getAmountDetails()->getAmount());
        $this->assertEquals($serviceCurrency, $service->getAmountDetails()->getCurrency());
        $this->assertEquals($serviceName, $service->getName());
        $this->assertEquals($serviceQuantity, $service->getQuantity());
        $this->assertEquals($taxReference, $incomeInformation->getTaxReference());
        $this->assertEquals($payerType, $incomeInformation->getPayerType());
    }

    public function testCreateSession(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $id ='test_ps_1',
                'status' => $sessionStatus = 'created',
                'created_at' => $createdAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $updatedAt = '2020-05-29T07:01:37.499907Z'
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->create(
            $this->createMock(CreateSessionRequest::class)
        );

        $this->assertEquals($status, $sessionResponse->getStatus());

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($id, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($createdAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($updatedAt), $session->getUpdatedAt());
    }

    public function testStartPaymentSession(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'in_progress',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http://bank131.ru'
                        ],
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->startPayment(
            $this->createMock(StartPaymentSessionRequest::class)
        );

        $this->assertEquals($sessionResponse->getStatus(), $status);

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());
    }

    public function testStartPayoutSession(): void
    {
        $expectedResponseBody = [
            "status"=> "ok",
            "session"=> [
                "id"=> $sessionId = "test_ps_1",
                "status"=> "in_progress",
                "created_at"=> $sessionCreatedAt = "2020-06-08T11:00:03.567464Z",
                "updated_at"=> $sessionUpdatedAt = "2020-06-08T11:10:03.625759Z",
                "payments"=> [
                    [
                        "id"=> $payoutId = "test_po_1",
                        "status"=> $payoutStatus = "in_progress",
                        "created_at"=> $payoutCreatedAt = "2020-06-08T11:10:03.618104Z",
                        "customer"=> [
                            "reference"=> $customerReference = "lucky"
                        ],
                        "payment_method"=> [
                            "type"=> "card",
                            "card"=> [
                                "brand"=> $cardBrand = "visa",
                                "last4"=> $cardLast4 = "4242"
                            ]
                        ],
                        "amount_details"=> [
                            "amount"=> $amount = 1000,
                            "currency"=> $currency = "rub"
                        ],
                        "participant_details"=> [
                            "recipient"=> [
                                "full_name"=> $recipientFullName = "John Doe"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->startPayout(
            $this->createMock(StartPayoutSessionRequest::class)
        );

        $this->assertTrue($sessionResponse->isOk());
        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertTrue($session->isInProgress());
        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getPayments());
        $this->assertCount(1, $session->getPayments());

        /** @var Payout $payout */
        $payout = $session->getPayments()[0];

        $this->assertEquals($payoutId, $payout->getId());
        $this->assertEquals($payoutStatus, $payout->getStatus());
        $this->assertEquals(new DateTimeImmutable($payoutCreatedAt), $payout->getCreatedAt());
        $this->assertEquals($customerReference, $payout->getCustomer()->getReference());
        $this->assertEquals($amount, $payout->getAmountDetails()->getAmount());
        $this->assertEquals($currency, $payout->getAmountDetails()->getCurrency());
        $this->assertEquals($cardBrand, $payout->getPaymentMethod()->getCard()->getBrand());
        $this->assertEquals($cardLast4, $payout->getPaymentMethod()->getCard()->getLast4());
        $this->assertEquals($recipientFullName, $payout->getParticipantDetails()->getRecipient()->getFullName());
    }

    public function testStartPayoutSessionWithFiscalization(): void
    {
        $expectedResponseBody = [
            "status"=> "ok",
            "session"=> [
                "id"=> $sessionId = "test_ps_1",
                "status"=> "in_progress",
                "created_at"=> $sessionCreatedAt = "2020-06-08T11:00:03.567464Z",
                "updated_at"=> $sessionUpdatedAt = "2020-06-08T11:10:03.625759Z",
                "payments"=> [
                    [
                        "id"=> $payoutId = "test_po_1",
                        "status"=> $payoutStatus = "in_progress",
                        "created_at"=> $payoutCreatedAt = "2020-06-08T11:10:03.618104Z",
                        "customer"=> [
                            "reference"=> $customerReference = "lucky"
                        ],
                        "payment_method"=> [
                            "type"=> "card",
                            "card"=> [
                                "brand"=> $cardBrand = "visa",
                                "last4"=> $cardLast4 = "4242"
                            ]
                        ],
                        "amount_details"=> [
                            "amount"=> $amount = 1000,
                            "currency"=> $currency = "rub"
                        ],
                        "fiscalization_details"=> [
                            "professional_income_taxpayer"=> [
                                "services" => [
                                    [
                                        "name" => $serviceName = "Test",
                                        "amount_details" => [
                                            "amount" => $serviceAmount = 1000,
                                            "currency" => $serviceCurrency = "rub"
                                        ],
                                        "quantity" => $serviceQuantity = 1
                                    ]
                                ],
                                "tax_reference" => $taxReference = "123456789012",
                                "payer_type" => $payerType = "individual"
                            ]
                        ],
                        "participant_details"=> [
                            "recipient"=> [
                                "full_name"=> $recipientFullName = "John Doe"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->startPayoutWithFiscalization(
            $this->createMock(StartPayoutSessionRequestWithFiscalization::class)
        );

        $this->assertTrue($sessionResponse->isOk());

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertTrue($session->isInProgress());
        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getPayments());
        $this->assertCount(1, $session->getPayments());

        /** @var Payout $payout */
        $payout = $session->getPayments()->get(0);

        $this->assertEquals($payoutId, $payout->getId());
        $this->assertEquals($payoutStatus, $payout->getStatus());
        $this->assertEquals(new DateTimeImmutable($payoutCreatedAt), $payout->getCreatedAt());
        $this->assertEquals($customerReference, $payout->getCustomer()->getReference());
        $this->assertEquals($amount, $payout->getAmountDetails()->getAmount());
        $this->assertEquals($currency, $payout->getAmountDetails()->getCurrency());
        $this->assertEquals($cardBrand, $payout->getPaymentMethod()->getCard()->getBrand());
        $this->assertEquals($cardLast4, $payout->getPaymentMethod()->getCard()->getLast4());
        $this->assertEquals($recipientFullName, $payout->getParticipantDetails()->getRecipient()->getFullName());

        $this->assertNotNull($fiscalizationDetails = $payout->getFiscalizationDetails());
        $this->assertNotNull($incomeInformation = $fiscalizationDetails->getProfessionalIncomeTaxpayer());

        $this->assertIsIterable($incomeInformation->getServices());
        $this->assertCount(1, $incomeInformation->getServices());

        /** @var FiscalizationService $service */
        $service = $incomeInformation->getServices()->get(0);

        $this->assertEquals($serviceAmount, $service->getAmountDetails()->getAmount());
        $this->assertEquals($serviceCurrency, $service->getAmountDetails()->getCurrency());
        $this->assertEquals($serviceName, $service->getName());
        $this->assertEquals($serviceQuantity, $service->getQuantity());
        $this->assertEquals($taxReference, $incomeInformation->getTaxReference());
        $this->assertEquals($payerType, $incomeInformation->getPayerType());
    }

    public function testRefundPaymentSession(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'succeeded',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'finished_at' => $paymentFinishedAt = '2020-05-29T07:01:40.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http://bank131.ru'
                        ],
                        "refunds" => [
                            [
                                "id"=> $refundId = "rf_101",
                                "status"=> $refundStatus = "in_progress",
                                "created_at"=> $refundCreatedAt = "2020-06-04T08:01:12.234932Z",
                                "amount_details"=> [
                                    "amount" => $refundAmount = 10000,
                                    "currency"=> $refundCurrency ="rub"
                                ],
                                "metadata" => $metadata
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->refund(
            $this->createMock(RefundPaymentSessionRequest::class)
        );

        $this->assertEquals($status, $sessionResponse->getStatus());

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());

        $this->assertIsIterable($acquiringPayment->getRefunds());
        $this->assertCount(1, $acquiringPayment->getRefunds());

        /** @var AcquiringPaymentRefund $refund */
        $refund = $acquiringPayment->getRefunds()[0];

        $this->assertTrue($refund->isInProgress());
        $this->assertEquals($refundId, $refund->getId());
        $this->assertEquals(new DateTimeImmutable($refundCreatedAt), $refund->getCreatedAt());
        $this->assertEquals($refundAmount, $refund->getAmountDetails()->getAmount());
        $this->assertEquals($refundCurrency, $refund->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $refund->getMetadata());
        $this->assertFalse($refund->getIsChargeback());
    }

    public function testSessionChargeback(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2022-08-15T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2022-08-15T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => 'in_progress',
                        'created_at' => '2022-08-15T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type' => 'card',
                            'card' => [
                                'brand' => 'visa',
                                'last4' => '4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount' => $amountValue = 10000,
                            'currency' => $amountCurrency = 'rub'
                        ],
                        'metadata' => $metadata = '{"key":"value"}',
                        'payment_options' => [
                            'return_url' => $returnUrl = 'http=>//bank131.ru'
                        ],
                        'refunds' => [
                            [
                                'id' => $refundId = 'rf_101',
                                'status' => $refundStatus = 'in_progress',
                                'created_at' => $refundCreatedAt = "2022-08-15T08:01:12.234932Z",
                                'amount_details' => [
                                    'amount' => $refundAmount = 10000,
                                    'currency' => $refundCurrency = 'rub'
                                ],
                                'metadata' => $metadata,
                                'is_chargeback' => true,
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->chargeback(
            $this->createMock(ChargebackPaymentSessionRequest::class)
        );

        $this->assertEquals($sessionResponse->getStatus(), $status);

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($paymentId, $acquiringPayment->getId());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());

        $this->assertIsIterable($acquiringPayment->getRefunds());
        $this->assertCount(1, $acquiringPayment->getRefunds());

        /** @var AcquiringPaymentRefund $refund */
        $refund = $acquiringPayment->getRefunds()[0];

        $this->assertTrue($refund->isInProgress());
        $this->assertEquals($refundId, $refund->getId());
        $this->assertEquals(new DateTimeImmutable($refundCreatedAt), $refund->getCreatedAt());
        $this->assertEquals($refundAmount, $refund->getAmountDetails()->getAmount());
        $this->assertEquals($refundCurrency, $refund->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $refund->getMetadata());
        $this->assertTrue($refund->getIsChargeback());
    }

    public function testSessionConfirm(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'in_progress',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http=>//bank131.ru'
                        ],
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->confirm($sessionId);

        $this->assertEquals($sessionResponse->getStatus(), $status);

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());
    }

    public function testSessionCancel(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'in_progress',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http=>//bank131.ru'
                        ],
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->cancel($sessionId);

        $this->assertEquals($sessionResponse->getStatus(), $status);

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());
    }

    public function testSessionCapture(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'in_progress',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http=>//bank131.ru'
                        ],
                    ]
                ]
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->capture($sessionId);

        $this->assertEquals($sessionResponse->getStatus(), $status);

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());
    }

    public function testSessionStatus(): void
    {
        $expectedResponseBody = [
            'status' => $status = 'ok',
            'session' => [
                'id' => $sessionId ='test_ps_1',
                'status' => $sessionStatus = 'in_progress',
                'created_at' => $sessionCreatedAt = '2020-05-29T07:01:37.499907Z',
                'updated_at' => $sessionUpdatedAt = '2020-05-29T07:01:37.499907Z',
                'acquiring_payments' => [
                    [
                        'id' => $paymentId = 'test_pm_1',
                        'status' => $paymentStatus = 'pending',
                        'created_at' => $paymentCreatedAt = '2020-05-29T07:01:37.499907Z',
                        'customer' => [
                            'reference' => $customerReference = 'lucky'
                        ],
                        'payment_details'=> [
                            'type'=> $paymentDetailsType = 'card',
                            'card'=> [
                                'brand'=> $cardBrand = 'visa',
                                'last4'=> $cardLastFour ='4242'
                            ]
                        ],
                        'amount_details'=> [
                            'amount'=> $amountValue =10000,
                            'currency'=> $amountCurrency = 'rub'
                        ],
                        'metadata'=> $metadata = '{"key":"value"}',
                        'payment_options'=> [
                            'return_url'=> $returnUrl = 'http=>//bank131.ru'
                        ],
                    ]
                ],
                'next_action' => 'confirm'
            ],
        ];

        $client = $this->createClientWithMockResponse([
            new Response(200, [], json_encode($expectedResponseBody))
        ]);

        $sessionResponse = $client->session()->status($sessionId);

        $this->assertEquals($sessionResponse->getStatus(), $status);

        $this->assertNotNull($session = $sessionResponse->getSession());

        $this->assertEquals($sessionId, $session->getId());
        $this->assertEquals($sessionStatus, $session->getStatus());
        $this->assertEquals(new DateTimeImmutable($sessionCreatedAt), $session->getCreatedAt());
        $this->assertEquals(new DateTimeImmutable($sessionUpdatedAt), $session->getUpdatedAt());
        $this->assertEquals('confirm', $session->getNextAction());

        $this->assertIsIterable($session->getAcquiringPayments());
        $this->assertCount(1, $session->getAcquiringPayments());

        /** @var AcquiringPayment $acquiringPayment */
        $acquiringPayment = $session->getAcquiringPayments()[0];

        $this->assertNotNull($acquiringPayment->getId());
        $this->assertNotNull($acquiringPayment->getStatus());
        $this->assertNotNull($acquiringPayment->getCreatedAt());
        $this->assertEquals($customerReference, $acquiringPayment->getCustomer()->getReference());
        $this->assertEquals($amountValue, $acquiringPayment->getAmountDetails()->getAmount());
        $this->assertEquals($amountCurrency, $acquiringPayment->getAmountDetails()->getCurrency());
        $this->assertEquals($metadata, $acquiringPayment->getMetadata());
        $this->assertEquals($returnUrl, $acquiringPayment->getPaymentOptions()->getReturnUrl());
    }
}
