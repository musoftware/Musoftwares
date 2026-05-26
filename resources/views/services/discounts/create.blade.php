@extends('layouts.app')

@section('title', __('New discount') . ' | ' . $service->title)

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header
            icon="ti ti-discount"
            title="{{ __('New discount') }}"
            subtitle="{{ __('Service') }}: {{ $service->title }}">
            <a href="{{ route('services.discounts.index', $service) }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to discounts') }}
            </a>
        </x-client.section-header>

        <x-client.form-card icon="plus" iconClass="text-primary" title="{{ __('Discount Details') }}">
            <form action="{{ route('services.discounts.store', $service) }}" method="POST">
                @csrf
                @include('services.discounts._form', ['discount' => null])
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('services.discounts.index', $service) }}" class="at-btn at-btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="at-btn at-btn-primary">{{ __('Create discount') }}</button>
                </div>
            </form>
        </x-client.form-card>
    </div>
@endsection
