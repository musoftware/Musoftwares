<?php

namespace App\Http\Resources\Tools;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolResellerTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'amount'        => (float) $this->amount,
            'balance_after' => (float) $this->balance_after,
            'currency'      => $this->currency,
            'description'   => $this->description,
            'user'          => $this->whenLoaded('user', fn() => ['name' => $this->user->name, 'email' => $this->user->email]),
            'created_at'    => $this->created_at->format('M d, Y H:i'),
        ];
    }
}
