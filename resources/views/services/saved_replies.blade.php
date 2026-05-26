@extends('layouts.app')

@section('title', __('common.saved_replies') . ' - ' . $service->title)

@section('description', __('services.saved_replies.meta_description', ['title' => $service->title]))

@section('og:image', asset($service->image))


@section('css_style')
@endsection

@section('additional_head_code')
    <link rel="canonical" href="{{ url()->current() }}"/>

    <meta property="og:title" content="{{ __('common.saved_replies') }} - {{ $service->title }}"/>
    <meta property="og:description"
          content="{{ __('services.saved_replies.meta_description', ['title' => $service->title]) }}"/>
    <meta property="og:url" content="{{ url()->current() }}"/>
    <meta property="og:type" content="website"/>
    <meta name="robots" content="noindex, nofollow"/>
@endsection

@section('breadcrumbs')
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('services.mindex') }}">{{ __('Services') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('services.show', $service) }}">{{ $service->title }}</a></li>
        <li class="breadcrumb-item active">{{ __('common.saved_replies') }}</li>
    </ol>
@endsection

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <span class="branding-tag">{{ __('Services') }}</span>
                <h1 class="at-admin-title mb-1">{{ __('common.saved_replies') }}</h1>
                <p class="at-admin-subtitle text-muted mb-0">{{ __('services.saved_replies.page_subtitle', ['title' => $service->title]) }}</p>
            </div>
            <a href="{{ route('services.show', $service) }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Service') }}
            </a>
        </div>
        <div class="at-card" style="border-radius: 8px;">
            <div class="at-card-body p-0">
                @livewire('saved-replies', ['service_id' => $service->id])
            </div>
        </div>
    </div>
@endsection
