@extends('layouts.app')

@section('title', 'Edit Serial')

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header 
            icon="ti ti-edit" 
            title="{{ __('general.edit_serial') }}" 
            subtitle="{{ __('general.update_serial_information') }}">
            <a href="{{ route('services.serials.index', $serial->service) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>{{ __('general.back_to_list') }}</a>
        </x-client.section-header>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <x-client.form-card icon="edit" iconClass="text-primary" title="{{ __('general.serial_details') }}">
                        <form action="{{ route('services.serials.update', [$serial->service, $serial]) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="serial" class="form-label">{{ __('general.serial_number') }}</label>
                                <input type="text" class="form-control" id="serial" name="serial"
                                    value="{{ old('serial', $serial->serial) }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available" {{ $serial->status === 'available' ? 'selected' : '' }}>
                                        Available</option>
                                    <option value="sold" {{ $serial->status === 'sold' ? 'selected' : '' }}>Sold</option>
                                    <option value="expired" {{ $serial->status === 'expired' ? 'selected' : '' }}>Expired
                                    </option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="expires_at" class="form-label">{{ __('general.expiration_date') }}</label>
                                <input type="date" class="form-control" id="expires_at" name="expires_at"
                                    value="{{ old('expires_at', $serial->expires_at ? $serial->expires_at->format('Y-m-d') : '') }}">
                                <div class="form-text">{{ __('general.leave_blank_if_this_serial_does_not_expire') }}</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('services.serials.index', $serial->service) }}"
                                    class="btn btn-light">{{ __('Cancel') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('Update Serial') }}</button>
                            </div>
                        </form>
                </x-client.form-card>
            </div>
        </div>
        </div>
    </div>
@endsection