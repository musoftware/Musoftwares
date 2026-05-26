@extends('layouts.app')

@section('title', __('Service Activation') . ' – ' . $service->title)

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <span class="branding-tag">{{ __('Admin') }}</span>
                <h1 class="at-admin-title mb-1">{{ __('Service Activation') }}</h1>
                <p class="at-admin-subtitle text-muted mb-0">{{ $service->title }}</p>
            </div>
            <a href="{{ route('admin.services.index') }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('services.back_to_services') }}
            </a>
        </div>

        <div class="at-card mb-4" style="border-radius: 8px;">
            <div class="at-card-body p-4">
                <h2 class="at-admin-subtitle mb-3">{{ __('Service Details') }}</h2>
                @php
                    $serviceDetailsData = [
                        [
                            'field' => __('services.service_image'),
                            'value' => '<img alt="' . e(__('Service image: :title', ['title' => $service->title])) . '" src="' . asset($service->image) . '" class="rounded" style="max-height: 80px;">'
                        ],
                        [
                            'field' => __('services.service_title'),
                            'value' => '<span class="fw-bold">' . e($service->title) . '</span>'
                        ],
                        [
                            'field' => __('services.service_desc'),
                            'value' => e($service->description)
                        ]
                    ];

                    $serviceDetailsHeaders = [
                        [
                            'key' => 'field',
                            'label' => __('Field'),
                            'class' => 'text-secondary',
                            'style' => 'white-space: nowrap; width: 1px;'
                        ],
                        [
                            'key' => 'value',
                            'label' => __('Value'),
                            'render' => function($item) {
                                return $item['value'];
                            }
                        ]
                    ];
                @endphp
                
                <x-ui.data-table 
                    :headers="$serviceDetailsHeaders"
                    :items="$serviceDetailsData"
                    class="table-bordered"
                />
            </div>
        </div>

        <div class="at-card" style="border-radius: 8px;">
            <div class="at-card-body p-4">
                <form action="{{ route('admin.services.update_status', $service) }}" method="POST">
                    @method('PATCH')
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label for="fake_orders_count" class="form-label">{{ __('Fake Orders Count') }}</label>
                            <input id="fake_orders_count" type="number" min="0" class="form-control @error('fake_orders_count') is-invalid @enderror"
                                name="fake_orders_count" value="{{ old('fake_orders_count', $service->fake_orders_count ?? 0) }}">
                            @error('fake_orders_count')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <label for="fake_last_delivery" class="form-label">{{ __('Fake Last Delivery') }}</label>
                            <input id="fake_last_delivery" type="datetime-local" class="form-control @error('fake_last_delivery') is-invalid @enderror"
                                name="fake_last_delivery" value="{{ old('fake_last_delivery', $service->fake_last_delivery ? $service->fake_last_delivery->format('Y-m-d\TH:i') : '') }}">
                            @error('fake_last_delivery')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <label for="status" class="form-label">{{ __('Status') }}</label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="pending" {{ $service->status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                <option value="approved" {{ $service->status == 'approved' ? 'selected' : '' }}>{{ __('admin.approved') }}</option>
                                <option value="declined" {{ $service->status == 'declined' ? 'selected' : '' }}>{{ __('Declined') }}</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="at-btn at-btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

