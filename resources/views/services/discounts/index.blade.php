@extends('layouts.app')

@section('title', __('Service discounts') . ' | ' . $service->title)

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header 
            icon="ti ti-discount" 
            title="{{ __('Service discounts') }}" 
            subtitle="{{ __('payment.manage_discounts') }} {{ $service->title }}">
            <a href="{{ route('services.show', $service) }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Service') }}
            </a>
            <a href="{{ route('services.discounts.create', $service) }}" class="at-btn at-btn-primary at-btn-sm">
                <i class="ti ti-plus me-1"></i>{{ __('New discount') }}
            </a>
        </x-client.section-header>

        <div class="at-card" style="border-radius: 8px;">
            <div class="at-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="py-3">{{ __('Type') }}</th>
                                <th class="py-3">{{ __('Discount') }}</th>
                                <th class="py-3">{{ __('Valid') }}</th>
                                <th class="py-3">{{ __('Status') }}</th>
                                <th class="text-end px-4 py-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($discounts as $d)
                                <tr>
                                    <td class="px-4">{{ $d->name ?: '—' }}</td>
                                    <td>
                                        @if($d->type === 'date_range')
                                            {{ __('services.date_range') }}
                                        @elseif($d->type === 'hijri_range')
                                            {{ __('Hijri dates') }}
                                        @elseif($d->type === 'days_from_now')
                                            {{ __('Valid :days days from creation', ['days' => $d->days_from_now]) }}
                                        @else
                                            {{ __('New users only') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($d->discount_type === 'percent')
                                            {{ $d->discount_value }}%
                                        @else
                                            {{ \App\Helpers\FinanceHelper::instance()->format_money($d->discount_value, $service->currency) }} {{ __('fixed') }}
                                        @endif
                                        @if($d->min_price_until !== null)
                                            <br><small class="text-muted">{{ __('Min. price') }}: {{ \App\Helpers\FinanceHelper::instance()->format_money($d->min_price_until, $service->currency) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($d->type === 'date_range' && $d->start_date && $d->end_date)
                                            {{ $d->start_date->format('Y-m-d') }} – {{ $d->end_date->format('Y-m-d') }}
                                        @elseif($d->type === 'hijri_range' && $d->hijri_start && $d->hijri_end)
                                            {{ $d->hijri_start }} – {{ $d->hijri_end }}
                                        @elseif($d->type === 'days_from_now')
                                            {{ $d->created_at->format('Y-m-d') }} +{{ $d->days_from_now }} {{ __('days') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="at-badge at-badge-{{ $d->active ? 'success' : 'secondary' }}">
                                            {{ $d->active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="dropdown">
                                            <button class="at-btn at-btn-ghost at-btn-sm" type="button" data-bs-toggle="dropdown" aria-label="{{ __('general.more_options') }}">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('services.discounts.edit', [$service, $d]) }}">
                                                        <i class="ti ti-edit me-2"></i>{{ __('Edit') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('services.discounts.destroy', [$service, $d]) }}" method="POST" class="d-inline"
                                                        onsubmit="return confirm('{{ __('Delete this discount?') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" aria-label="Delete">
                                                            <i class="ti ti-trash me-2"></i>{{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="ti ti-discount-off fs-1 mb-3 d-block"></i>
                                        {{ __('No discounts yet. Create one to run promotions (e.g. Ramadan, new users).') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($discounts->hasPages())
                <div class="p-3 border-top d-flex justify-content-center" style="border-color: #eee;">
                    {{ $discounts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
