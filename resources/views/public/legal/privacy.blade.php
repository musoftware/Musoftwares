@extends('layouts.public')

@section('content')
@php($legal = $legalInfo ?? \App\Support\PrivacySettings::legalInfo())
<div class="w-full overflow-x-clip bg-[#fbfbfd] text-[#1d1d1f] font-sans selection:bg-[#0071e3]/20 selection:text-[#0071e3] pt-16 sm:pt-24 pb-24 sm:pb-36">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-4 mb-14">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#0071e3]">
            {{ __('landing_legal.legal_center') }}
        </p>
        <h1 class="text-3xl sm:text-5xl font-bold text-[#1d1d1f] tracking-tight">
            {{ __('landing_legal.privacy_title') }}
        </h1>
        <p class="text-sm sm:text-base text-[#1d1d1f]/70 leading-relaxed max-w-3xl">
            {{ __('landing_legal.privacy_subtitle') }}
        </p>
        <p class="text-xs text-[#1d1d1f]/50 font-medium pt-1">
            {{ __('landing_legal.last_updated') }} ({{ $legal['privacy_policy_version'] }})
        </p>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-8 text-sm leading-relaxed text-[#1d1d1f]/80">
        {{-- 1. Controller Identity --}}
        <section id="controller" class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-lg sm:text-xl font-bold text-[#1d1d1f]">
                {{ __('privacy_compliance.controller_section_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 leading-relaxed">
                {{ __('landing_legal.privacy_intro_body') }}
            </p>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-black/5 text-xs sm:text-sm">
                <div>
                    <dt class="font-semibold text-[#1d1d1f]">{{ __('privacy_compliance.controller_legal_name_label') }}</dt>
                    <dd class="text-[#1d1d1f]/75 mt-1">{{ $legal['legal_company_name'] }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#1d1d1f]">{{ __('privacy_compliance.controller_headquarters_label') }}</dt>
                    <dd class="text-[#1d1d1f]/75 mt-1">{{ $legal['legal_headquarters_address'] }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#1d1d1f]">{{ __('privacy_compliance.controller_registry_label') }}</dt>
                    <dd class="text-[#1d1d1f]/75 mt-1">{{ $legal['legal_commercial_registry'] }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#1d1d1f]">{{ __('privacy_compliance.controller_policy_version_label') }}</dt>
                    <dd class="text-[#1d1d1f]/75 mt-1 font-mono">{{ $legal['privacy_policy_version'] }}</dd>
                </div>
            </dl>
        </section>

        {{-- 2. Detailed Personal Data Inventory, Purpose & Retention Schedule --}}
        <section id="data-table" class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-lg sm:text-xl font-bold text-[#1d1d1f]">
                {{ __('privacy_compliance.inventory_table_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 leading-relaxed">
                {{ __('landing_legal.privacy_data_collect_body') }}
            </p>
            <div class="overflow-x-auto border border-black/10 rounded-xl">
                <table class="w-full text-start border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#f5f5f7] border-b border-black/10 text-[#1d1d1f]">
                            <th class="p-3 sm:p-4 font-semibold text-start">{{ __('privacy_compliance.inventory_col_data') }}</th>
                            <th class="p-3 sm:p-4 font-semibold text-start">{{ __('privacy_compliance.inventory_col_purpose') }}</th>
                            <th class="p-3 sm:p-4 font-semibold text-start">{{ __('privacy_compliance.inventory_col_retention') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ([1, 2, 3, 4, 5] as $row)
                            <tr>
                                <td class="p-3 sm:p-4 font-medium text-[#1d1d1f] align-top">{{ __("privacy_compliance.inventory_row_{$row}_data") }}</td>
                                <td class="p-3 sm:p-4 text-[#1d1d1f]/75 align-top">{{ __("privacy_compliance.inventory_row_{$row}_purpose") }}</td>
                                <td class="p-3 sm:p-4 text-[#1d1d1f]/75 align-top">{{ __("privacy_compliance.inventory_row_{$row}_retention") }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 3. Third-Party Processors & Cross-Border Cloud Hosting --}}
        <section id="processors" class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-lg sm:text-xl font-bold text-[#1d1d1f]">
                {{ __('privacy_compliance.processors_section_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 leading-relaxed">
                {{ __('landing_legal.privacy_data_sharing_body') }}
            </p>
            <ul class="list-disc ps-5 space-y-2.5 text-xs sm:text-sm text-[#1d1d1f]/80">
                <li>{{ __('privacy_compliance.processors_payment') }}</li>
                <li>{{ __('privacy_compliance.processors_hosting', ['regions' => $legal['hosting_regions_disclosure']]) }}</li>
                <li>{{ __('privacy_compliance.processors_comms') }}</li>
            </ul>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/70 pt-2 border-t border-black/5 leading-relaxed">
                {{ __('landing_legal.privacy_data_security_body') }}
            </p>
        </section>

        {{-- 4. User Rights & 6-Business-Day Response SLA --}}
        <section id="rights" class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-lg sm:text-xl font-bold text-[#1d1d1f]">
                {{ __('privacy_compliance.rights_sla_section_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/75 leading-relaxed">
                {{ __('privacy_compliance.rights_sla_intro') }}
            </p>
            <ol class="list-decimal ps-5 space-y-2 text-xs sm:text-sm text-[#1d1d1f]/80">
                <li>{{ __('privacy_compliance.rights_step_1') }}</li>
                <li>{{ __('privacy_compliance.rights_step_2') }}</li>
                <li>{{ __('privacy_compliance.rights_step_3') }}</li>
            </ol>
        </section>

        {{-- 5. Designated Data Protection Officer (DPO) Contact --}}
        <section id="dpo" class="bg-white border border-black/10 rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-lg sm:text-xl font-bold text-[#1d1d1f]">
                {{ __('privacy_compliance.dpo_section_title') }}
            </h2>
            <p class="text-xs sm:text-sm text-[#1d1d1f]/75 leading-relaxed">
                {{ __('privacy_compliance.dpo_section_body') }}
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-black/5 text-xs sm:text-sm">
                <div>
                    <span class="block font-semibold text-[#1d1d1f]">{{ __('privacy_compliance.dpo_email_label') }}</span>
                    <a href="mailto:{{ $legal['dpo_email'] }}" class="text-[#0071e3] hover:underline font-medium break-all">
                        {{ $legal['dpo_email'] }}
                    </a>
                </div>
                <div>
                    <span class="block font-semibold text-[#1d1d1f]">{{ __('privacy_compliance.dpo_phone_label') }}</span>
                    <a href="tel:{{ preg_replace('/\s+/', '', $legal['dpo_phone']) }}" dir="ltr" class="text-[#0071e3] hover:underline font-medium inline-block">
                        {{ $legal['dpo_phone'] }}
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
