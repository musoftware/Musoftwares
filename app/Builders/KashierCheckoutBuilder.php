<?php

namespace App\Builders;

use App\Helpers\KashierHelper;
use App\Models\CurrenciesExchange;
use App\Models\Currency;

class KashierCheckoutBuilder
{
    private float $amount = 0;

    private string $currency = 'EGP';

    private string $orderIdPrefix = 'order_';

    private string $uniqueIdentifier = '';

    private array $metaData = [];

    private array $customer = [];

    private string $customerReference = '';

    private string $successUrl = '';

    private string $failureUrl = '';

    private string $webhookUrl = '';

    private string $mode;

    private string $merchantId;

    private function __construct()
    {
        $this->mode = config('services.kashier.mode', 'live');
        $this->merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $this->uniqueIdentifier = uniqid();
    }

    public static function make(): self
    {
        return new self;
    }

    public function forAmount(float $amount, string $currency = 'EGP'): self
    {
        $this->amount = $amount;
        $this->currency = strtoupper($currency);

        return $this;
    }

    public function forUser(int $userId, string $name, string $email): self
    {
        $this->customer = [
            'firstName' => $name,
            'email' => $email,
            'reference' => 'user_'.$userId,
        ];
        $this->customerReference = 'user_'.$userId;
        $this->metaData['user_id'] = $userId;

        return $this;
    }

    public function forGuest(string $name, string $email, string $reference): self
    {
        $this->customer = [
            'firstName' => $name,
            'email' => $email,
            'reference' => $reference,
        ];
        $this->customerReference = $reference;

        return $this;
    }

    public function withSource(string $source, string $orderIdPrefix = 'order_'): self
    {
        $this->metaData['source'] = $source;
        $this->orderIdPrefix = $orderIdPrefix;

        return $this;
    }

    public function withMetadata(array $data): self
    {
        $this->metaData = array_merge($this->metaData, $data);

        return $this;
    }

    public function withRoutes(string $success, string $failure, string $webhook): self
    {
        $this->successUrl = urlencode($success);
        $this->failureUrl = urlencode($failure);
        $this->webhookUrl = urlencode($webhook);

        return $this;
    }

    private function prepareEgpConversion(): void
    {
        $originalCurrency = strtoupper($this->currency);

        if ($originalCurrency !== 'EGP') {
            $currencyModel = Currency::where('currency', $originalCurrency)->first();
            $egpModel = Currency::where('currency', 'EGP')->first();

            if ($currencyModel && $egpModel) {
                // Keep track of original values
                $this->metaData['original_amount'] = $this->amount;
                $this->metaData['original_currency'] = $originalCurrency;

                // Convert amount to EGP using today's rate
                $this->amount = CurrenciesExchange::RateToday($this->amount, $currencyModel->id, $egpModel->id);
            }
            $this->currency = 'EGP';
        }
    }

    public function build(): string
    {
        // Allow adding a specific ID to the order prefix if provided in metadata (like invoice_id)
        $identifier = '';
        if (isset($this->metaData['invoice_id'])) {
            $identifier = $this->metaData['invoice_id'].'_';
        }
        if (isset($this->metaData['payment_link_id'])) {
            $identifier = $this->metaData['payment_link_id'].'_';
        }
        if (isset($this->metaData['package_id'])) {
            $identifier = $this->metaData['package_id'].'_';
        }

        // Finalize order ID
        $orderId = $this->orderIdPrefix.$identifier.$this->uniqueIdentifier.(isset($this->metaData['user_id']) ? '-'.$this->metaData['user_id'] : '');

        // Handle Currency Conversion to EGP
        $this->prepareEgpConversion();

        // Generate Hash
        $hash = KashierHelper::generateHash(
            $this->merchantId,
            $orderId,
            $this->amount,
            $this->currency,
            $this->customerReference
        );

        $params = [
            'merchantId' => $this->merchantId,
            'orderId' => $orderId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'hash' => $hash,
            'mode' => $this->mode,
            'merchantRedirect' => $this->successUrl,
            'serverWebhook' => $this->webhookUrl,
            'failureRedirect' => $this->failureUrl,
            'redirectMethod' => 'get',
            'type' => 'external',
            'brandColor' => '#4f46e5',
            'display' => app()->getLocale() ?: 'en',
            'manualCapture' => 'false',
            'customer' => json_encode($this->customer),
            'saveCard' => 'optional',
            'interactionSource' => 'Ecommerce',
            'enable3DS' => 'true',
            'allowedMethods' => 'card,wallet',
            'CustomerReference' => $this->customerReference,
            'metaData' => json_encode($this->metaData),
        ];

        return 'https://payments.kashier.io/?'.http_build_query($params);
    }
}
