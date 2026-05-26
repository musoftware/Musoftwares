@extends('layouts.app')

@section('title', __('Edit discount') . ' | ' . $service->title)

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header
            icon="ti ti-edit"
            title="{{ __('Edit discount') }}"
            subtitle="{{ __('Service') }}: {{ $service->title }}">
            <a href="{{ route('services.discounts.index', $service) }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to discounts') }}
            </a>
        </x-client.section-header>

        <x-client.form-card icon="edit" iconClass="text-primary" title="{{ __('Discount Details') }}">
            <form action="{{ route('services.discounts.update', [$service, $discount]) }}" method="POST">
                @csrf
                @method('PUT')
                @include('services.discounts._form', ['discount' => $discount])
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('services.discounts.index', $service) }}" class="at-btn at-btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="at-btn at-btn-primary">{{ __('Update discount') }}</button>
                </div>
            </form>
        </x-client.form-card>
    </div>
@endsection
