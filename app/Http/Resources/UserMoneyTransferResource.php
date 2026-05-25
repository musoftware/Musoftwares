<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMoneyTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => (float) $this->amount,
            'currency_id'    => $this->currency_id,
            'status'         => $this->status,
            'reason'         => $this->reason,
            'fee_amount'     => (float) $this->fee_amount,
            'exchange_rate'  => (float) $this->exchange_rate,
            'converted_amount' => (float) $this->converted_amount,
            'admin_notes'    => $this->admin_notes,
            'sender'         => $this->whenLoaded('sender', function () {
                return [
                    'id'    => $this->sender->id,
                    'name'  => $this->sender->name,
                    'email' => $this->sender->email,
                ];
            }),
            'receiver'       => $this->whenLoaded('receiver', function () {
                return [
                    'id'    => $this->receiver->id,
                    'name'  => $this->receiver->name,
                    'email' => $this->receiver->email,
                ];
            }),
            'currency'       => $this->whenLoaded('currencyModel', function () {
                return [
                    'id'       => $this->currencyModel->id,
                    'currency' => $this->currencyModel->currency,
                ];
            }),
            'processed_at'   => $this->processed_at?->toIso8601String(),
            'cancelled_at'   => $this->cancelled_at?->toIso8601String(),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
