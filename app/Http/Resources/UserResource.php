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
            'avatar_url'           => 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?s=200&d=mp',
            'initials'             => $initials,
            'role'                 => $this->whenLoaded('roles', fn() => $this->roles->first()?->name ?? 'user', 'user'),
            'account_status'       => $this->account_status ?? 'active',
            'block_reason'         => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports') || $request->routeIs('admin.users.problematic'), $this->block_reason),
            'email_verified_at'    => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->email_verified_at),
            'phone'                => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->phone),
            'mobile_1'             => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->mobile_1),
            'mobile_2'             => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->mobile_2),
            'whatsapp_number'      => $this->whatsapp_number,
            'telegram_username'    => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->telegram_username),
            'country'              => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->country),
            'city'                 => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->city),
            'currency'             => $this->currency_name(),
            'kyc_verified'         => (bool) $this->kyc_verified,
            'kyc_verified_at'      => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->kyc_verified_at),
            'kyc_verified_by'      => $this->whenLoaded('kycVerifier', fn() => $this->kycVerifier?->name),
            'kyc_notes'            => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->kyc_notes),
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
            'available_balance'    => (float) $this->available_balance(),
            'onboarding_completed' => (bool) $this->onboarding_completed,
            'last_activity_at'     => $this->last_activity_at,
            'created_at'           => $this->created_at,

            // Added for Admin User Profile Migration
            'facebook'             => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->facebook),
            'skype'                => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->skype),
            'job'                  => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->job),
            'address'              => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->address),
            'date_start'           => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->date_start),
            'date_end'             => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->date_end),
            'client_taxable'       => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (bool) $this->client_taxable),
            'invoice_taxable'      => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (bool) $this->invoice_taxable),
            'timer_taxable'        => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (bool) $this->timer_taxable),
            'hour_rate'            => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->hour_rate),
            'allow_referral_system'=> $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (bool) $this->allow_referral_system),
            'affiliate_commission_percentage'=> $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->affiliate_commission_percentage),
            'add_commission_to_total'=> $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (bool) $this->add_commission_to_total),
            'ref_user_id'          => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->ref_user_id),
            
            'subscription_date'    => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->subscription_date),
            'subscription_plan'    => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->subscription_plan),
            
            'total_paid'           => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (float) $this->total_paid),
            'total_cost'           => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (float) $this->total_cost),
            'pending_commission'   => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (float) $this->pending_commission),
            'user_balance'         => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), (float) $this->user_balance),
            'slug'                 => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->slug),
            'referrals_count'      => $this->when($request->routeIs('admin.users.show') || $request->routeIs('admin.users.reports'), $this->my_ref_users()->count()),
        ];
    }
}
