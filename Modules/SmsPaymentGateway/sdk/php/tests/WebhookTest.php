<?php

namespace SmsPay\Tests;

use PHPUnit\Framework\TestCase;
use SmsPay\Exception\ApiException;
use SmsPay\SmsPayObject;
use SmsPay\Webhook;

/**
 * Unit tests for the SmsPay Webhook signature verification.
 */
class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret_key';

    /**
     * Helper: build a valid signed payload.
     *
     * @param array<string, mixed> $eventData
     * @param int|null             $timestamp
     * @return array{payload: string, signature: string, timestamp: string}
     */
    private function buildSignedEvent(array $eventData, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $payload   = json_encode($eventData);
        $signed    = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signed, self::SECRET);

        return [
            'payload'   => $payload,
            'signature' => $signature,
            'timestamp' => (string) $timestamp,
        ];
    }

    // -------------------------------------------------------------------------
    //  Successful verification
    // -------------------------------------------------------------------------

    public function testConstructEventReturnsValidEvent(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => [
                'id'     => 'cs_test_001',
                'status' => 'complete',
                'amount' => 150.00,
            ],
        ];

        $signed = $this->buildSignedEvent($eventData);

        $event = Webhook::constructEvent(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET
        );

        $this->assertInstanceOf(SmsPayObject::class, $event);
        $this->assertSame('checkout.session.completed', $event->type);
        $this->assertInstanceOf(SmsPayObject::class, $event->data);
        $this->assertSame('cs_test_001', $event->data->id);
        $this->assertSame('complete', $event->data->status);
        $this->assertSame(150.00, $event->data->amount);
    }

    public function testConstructEventWithLargeTolerance(): void
    {
        $eventData = [
            'type' => 'checkout.session.expired',
            'data' => ['id' => 'cs_test_002'],
        ];

        // Timestamp 100 seconds in the past — still within a 300s tolerance.
        $signed = $this->buildSignedEvent($eventData, time() - 100);

        $event = Webhook::constructEvent(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET
        );

        $this->assertSame('checkout.session.expired', $event->type);
    }

    public function testConstructEventWithZeroToleranceSkipsTimeCheck(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cs_test_003'],
        ];

        // Very old timestamp — should pass when tolerance is 0 (disabled).
        $signed = $this->buildSignedEvent($eventData, time() - 99999);

        $event = Webhook::constructEvent(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET,
            0 // Tolerance disabled.
        );

        $this->assertSame('checkout.session.completed', $event->type);
    }

    // -------------------------------------------------------------------------
    //  Signature failures
    // -------------------------------------------------------------------------

    public function testInvalidSignatureThrows(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cs_test_001'],
        ];

        $signed = $this->buildSignedEvent($eventData);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('signature verification failed');

        Webhook::constructEvent(
            $signed['payload'],
            'invalid_signature_value',
            $signed['timestamp'],
            self::SECRET
        );
    }

    public function testWrongSecretThrows(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cs_test_001'],
        ];

        $signed = $this->buildSignedEvent($eventData);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('signature verification failed');

        Webhook::constructEvent(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            'whsec_wrong_secret'
        );
    }

    public function testTamperedPayloadThrows(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cs_test_001', 'amount' => 150.00],
        ];

        $signed = $this->buildSignedEvent($eventData);

        // Tamper with the payload after signing.
        $tampered = str_replace('150', '999', $signed['payload']);

        $this->expectException(ApiException::class);

        Webhook::constructEvent(
            $tampered,
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET
        );
    }

    // -------------------------------------------------------------------------
    //  Timestamp tolerance
    // -------------------------------------------------------------------------

    public function testStaleTimestampThrows(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cs_test_001'],
        ];

        // 10 minutes ago — exceeds the default 5-minute tolerance.
        $signed = $this->buildSignedEvent($eventData, time() - 600);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('tolerance');

        Webhook::constructEvent(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET
        );
    }

    public function testFutureTimestampBeyondToleranceThrows(): void
    {
        $eventData = [
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cs_test_001'],
        ];

        // 10 minutes in the future.
        $signed = $this->buildSignedEvent($eventData, time() + 600);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('tolerance');

        Webhook::constructEvent(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET
        );
    }

    // -------------------------------------------------------------------------
    //  Malformed payload
    // -------------------------------------------------------------------------

    public function testInvalidJsonPayloadThrows(): void
    {
        $badPayload = 'this-is-not-json';
        $timestamp  = (string) time();
        $signature  = hash_hmac('sha256', $timestamp . '.' . $badPayload, self::SECRET);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid webhook payload');

        Webhook::constructEvent($badPayload, $signature, $timestamp, self::SECRET);
    }

    // -------------------------------------------------------------------------
    //  verifySignature standalone
    // -------------------------------------------------------------------------

    public function testVerifySignaturePassesOnValidInput(): void
    {
        $eventData = ['type' => 'test'];
        $signed    = $this->buildSignedEvent($eventData);

        // Should complete without throwing.
        Webhook::verifySignature(
            $signed['payload'],
            $signed['signature'],
            $signed['timestamp'],
            self::SECRET
        );

        // If we reach here, verification passed.
        $this->assertTrue(true);
    }
}
