@extends('layouts.app')

@section('title', __('My Services'))

@push('scripts')
    <script src="{{ asset('js/responsive-table.js') }}"></script>
@endpush

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix admin-table-mobile-cards services-index-page services-page">
        <x-client.section-header
            icon="ti ti-briefcase"
            title="{{ __('My Services') }}"
            subtitle="{{ __('Manage your services and offerings') }}">
            <div class="header-actions-row">
                <x-ui.search-input 
                    placeholder="{{ __('Search services...') }}"
                    :showCategory="false"
                />
                <div class="section-header-actions">
                    <a href="{{ route('services.create') }}" class="at-btn at-btn-primary at-btn-sm">
                        <i class="ti ti-plus me-1" aria-hidden="true"></i>{{ __('Add Service') }}
                    </a>
                    <a href="{{ route('my-orders.mindex') }}" class="at-btn at-btn-ghost at-btn-sm d-flex align-items-center position-relative">
                        <i class="ti ti-package me-2" aria-hidden="true"></i>{{ __('My Orders') }}
                        @php $activeOrders = Auth::user()->seller_orders()->where('service_orders.status', 'active')->count(); @endphp
                        @if($activeOrders > 0)
                            <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" aria-label="{{ __('Active orders') }}: {{ $activeOrders }}">{{ $activeOrders }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </x-client.section-header>

        <div class="services-index-content">
            <div class="at-card action-card-modern admin-table-card services-table-card">
                <div class="services-card-header">
                    <span class="branding-tag">{{ __('Services') }}</span>
                    <p class="services-card-subtitle mb-0">
                        @if($services->total() > 0)
                            {{ $services->total() }} {{ __('Services') }}
                        @else
                            {{ __('Your service list') }}
                        @endif
                    </p>
                </div>
                <div class="at-card-body p-0">
                    <x-ui.services-table :services="$services" />
                </div>
            </div>
        </div>
        @if($services->hasPages())
            <div class="services-pagination border-top">
                {{ $services->links() }}
            </div>
        @endif
    </div>
@endsection
