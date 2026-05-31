<?php

namespace SmsPay\Tests;

use PHPUnit\Framework\TestCase;
use SmsPay\SmsPay;
use SmsPay\SmsPayObject;

/**
 * Unit tests for the SmsPay SDK core classes.
 *
 * These tests validate client initialisation, SmsPayObject hydration,
 * and exception handling without making real HTTP requests.
 */
class SmsPayTest extends TestCase
{
    // -------------------------------------------------------------------------
    //  SmsPay Client
    // -------------------------------------------------------------------------

    public function testConstructorStoresApiKey(): void
    {
        $client = new SmsPay('sk_test_abc123');
        $this->assertSame('sk_test_abc123', $client->getApiKey());
    }

    public function testConstructorAcceptsCustomBaseUrl(): void
    {
        $client = new SmsPay('sk_test_abc123', [
            'base_url' => 'https://custom.gateway.dev',
        ]);

        // Reaching this point proves the constructor accepted the option.
        $this->assertSame('sk_test_abc123', $client->getApiKey());
    }

    public function testConstructorThrowsOnEmptyApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SmsPay('');
    }

    public function testConstructorThrowsOnWhitespaceApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SmsPay('   ');
    }

    public function testCheckoutSessionsPropertyIsAccessible(): void
    {
        $client = new SmsPay('sk_test_abc123');

        $this->assertInstanceOf(
            \SmsPay\Resources\CheckoutSessions::class,
            $client->checkoutSessions
        );
    }

    public function testHttpClientIsAccessible(): void
    {
        $client = new SmsPay('sk_test_abc123');

        $this->assertInstanceOf(
            \SmsPay\HttpClient\CurlClient::class,
            $client->getHttpClient()
        );
    }

    // -------------------------------------------------------------------------
    //  SmsPayObject
    // -------------------------------------------------------------------------

    public function testFromArrayCreatesObjectWithProperties(): void
    {
        $obj = SmsPayObject::fromArray([
            'id'     => 'cs_test_001',
            'status' => 'open',
            'amount' => 150.00,
        ]);

        $this->assertSame('cs_test_001', $obj->id);
        $this->assertSame('open', $obj->status);
        $this->assertSame(150.00, $obj->amount);
    }

    public function testMissingPropertyReturnsNull(): void
    {
        $obj = SmsPayObject::fromArray(['id' => 'cs_test_001']);

        $this->assertNull($obj->nonexistent);
    }

    public function testIssetReturnsTrueForExistingProperty(): void
    {
        $obj = SmsPayObject::fromArray(['id' => 'cs_test_001']);

        $this->assertTrue(isset($obj->id));
        $this->assertFalse(isset($obj->missing));
    }

    public function testToArrayReturnsOriginalData(): void
    {
        $data = [
            'id'       => 'cs_test_001',
            'status'   => 'complete',
            'metadata' => ['order_id' => '999'],
        ];

        $obj = SmsPayObject::fromArray($data);

        $this->assertSame($data, $obj->toArray());
    }

    public function testNestedObjectWithObjectKey(): void
    {
        $data = [
            'id'   => 'evt_001',
            'data' => [
                'object' => 'checkout.session',
                'id'     => 'cs_test_001',
                'status' => 'complete',
            ],
        ];

        $obj = SmsPayObject::fromArray($data);

        $this->assertInstanceOf(SmsPayObject::class, $obj->data);
        $this->assertSame('cs_test_001', $obj->data->id);
        $this->assertSame('complete', $obj->data->status);
    }

    public function testToStringReturnsJson(): void
    {
        $obj = SmsPayObject::fromArray(['id' => 'cs_test_001']);
        $json = (string) $obj;

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('cs_test_001', $decoded['id']);
    }

    // -------------------------------------------------------------------------
    //  Exceptions
    // -------------------------------------------------------------------------

    public function testApiExceptionCarriesStatusAndBody(): void
    {
        $body = json_encode(['error' => 'Server error']);
        $exception = new \SmsPay\Exception\ApiException('Server error', 500, $body);

        $this->assertSame(500, $exception->getHttpStatus());
        $this->assertSame($body, $exception->getHttpBody());
        $this->assertSame(['error' => 'Server error'], $exception->getJsonBody());
    }

    public function testAuthenticationExceptionHas401Status(): void
    {
        $exception = new \SmsPay\Exception\AuthenticationException('Bad key');

        $this->assertSame(401, $exception->getHttpStatus());
        $this->assertInstanceOf(\SmsPay\Exception\ApiException::class, $exception);
    }

    public function testInvalidRequestExceptionCarriesParam(): void
    {
        $exception = new \SmsPay\Exception\InvalidRequestException(
            'Missing field',
            'amount',
            422,
            '{"error":"Missing field"}'
        );

        $this->assertSame(422, $exception->getHttpStatus());
        $this->assertSame('amount', $exception->getErrorParam());
        $this->assertInstanceOf(\SmsPay\Exception\ApiException::class, $exception);
    }

    public function testInvalidRequestExceptionDefaultsTo400(): void
    {
        $exception = new \SmsPay\Exception\InvalidRequestException('Bad request');

        $this->assertSame(400, $exception->getHttpStatus());
        $this->assertNull($exception->getErrorParam());
    }
}
