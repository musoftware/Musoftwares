<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $initials = collect(explode(' ', $this->name))
            ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8'))
            ->take(2)
            ->implode('');

        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'initials'             => $initials,
            'role'                 => $this->whenLoaded('roles', fn() => $this->roles->first()?->name ?? 'user', 'user'),
            'account_status'       => $this->account_status ?? 'active',
            'block_reason'         => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.problematic'), $this->block_reason),
            'email_verified_at'    => $this->when($request->routeIs('admin.users.show'), $this->email_verified_at),
            'phone'                => $this->when($request->routeIs('admin.users.show'), $this->phone),
            'mobile_1'             => $this->when($request->routeIs('admin.users.show'), $this->mobile_1),
            'mobile_2'             => $this->when($request->routeIs('admin.users.show'), $this->mobile_2),
            'whatsapp_number'      => $this->whatsapp_number,
            'telegram_username'    => $this->when($request->routeIs('admin.users.show'), $this->telegram_username),
            'country'              => $this->when($request->routeIs('admin.users.show'), $this->country),
            'city'                 => $this->when($request->routeIs('admin.users.show'), $this->city),
            'preferred_currency'   => $this->preferred_currency ?? 'USD',
            'kyc_verified'         => (bool) $this->kyc_verified,
            'kyc_verified_at'      => $this->when($request->routeIs('admin.users.show'), $this->kyc_verified_at),
            'kyc_verified_by'      => $this->whenLoaded('kycVerifier', fn() => $this->kycVerifier?->name),
            'kyc_notes'            => $this->when($request->routeIs('admin.users.show'), $this->kyc_notes),
            'kyc_documents'        => $this->whenLoaded('kycDocuments', fn() => $this->kycDocuments->map(fn($d) => [
                'id'          => $d->id,
                'type'        => $d->document_type,
                'status'      => $d->status,
                'uploaded_at' => $d->created_at,
            ])),
            'support_tickets'      => $this->whenLoaded('tickets', fn() => $this->tickets->map(fn($t) => [
                'id' => $t->id,
                'subject' => $t->subject,
                'status' => $t->ticket_status ?? $t->status,
            ])),
            'onboarding_completed' => (bool) $this->onboarding_completed,
            'last_activity_at'     => $this->last_activity_at,
            'created_at'           => $this->created_at,
        ];
    }
}
