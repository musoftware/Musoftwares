@php
    $discount = $discount ?? null;
    $isEdit = $discount !== null;
@endphp

<div class="mb-3">
    <label for="name" class="form-label">{{ __('Name (optional)') }}</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
        value="{{ old('name', $discount?->name) }}" placeholder="{{ __('e.g. Ramadan 2026') }}">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="type" class="form-label">{{ __('Discount type') }}</label>
    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
        <option value="date_range" {{ old('type', $discount?->type) === 'date_range' ? 'selected' : '' }}>{{ __('Date range (e.g. start – end)') }}</option>
        <option value="hijri_range" {{ old('type', $discount?->type) === 'hijri_range' ? 'selected' : '' }}>{{ __('Hijri date range') }}</option>
        <option value="days_from_now" {{ old('type', $discount?->type) === 'days_from_now' ? 'selected' : '' }}>{{ __('Valid X days from creation') }}</option>
        <option value="new_users_only" {{ old('type', $discount?->type) === 'new_users_only' ? 'selected' : '' }}>{{ __('New users only') }}</option>
    </select>
    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div id="date-range-fields" class="discount-type-fields mb-3" style="display: {{ old('type', $discount?->type) === 'date_range' ? 'block' : 'none' }};">
    <div class="row">
        <div class="col-md-6">
            <label for="start_date" class="form-label">{{ __('Start date') }}</label>
            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date"
                value="{{ old('start_date', $discount?->start_date?->format('Y-m-d')) }}">
            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="end_date" class="form-label">{{ __('End date') }}</label>
            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date"
                value="{{ old('end_date', $discount?->end_date?->format('Y-m-d')) }}">
            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div id="hijri-fields" class="discount-type-fields mb-3" style="display: {{ old('type', $discount?->type) === 'hijri_range' ? 'block' : 'none' }};">
    <div class="row">
        <div class="col-md-6">
            <label for="hijri_start" class="form-label">{{ __('Hijri start (e.g. 1446-09-01)') }}</label>
            <input type="text" class="form-control @error('hijri_start') is-invalid @enderror" id="hijri_start" name="hijri_start"
                value="{{ old('hijri_start', $discount?->hijri_start) }}" placeholder="1446-09-01">
            @error('hijri_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="hijri_end" class="form-label">{{ __('Hijri end') }}</label>
            <input type="text" class="form-control @error('hijri_end') is-invalid @enderror" id="hijri_end" name="hijri_end"
                value="{{ old('hijri_end', $discount?->hijri_end) }}" placeholder="1446-09-30">
            @error('hijri_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div id="days-from-now-fields" class="discount-type-fields mb-3" style="display: {{ old('type', $discount?->type) === 'days_from_now' ? 'block' : 'none' }};">
    <label for="days_from_now" class="form-label">{{ __('Valid for how many days from creation?') }}</label>
    <input type="number" class="form-control @error('days_from_now') is-invalid @enderror" id="days_from_now" name="days_from_now"
        value="{{ old('days_from_now', $discount?->days_from_now) }}" min="1" max="365" placeholder="3">
    <div class="form-text">{{ __('e.g. 3 = discount applies for 3 days from when you create it') }}</div>
    @error('days_from_now')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div id="new-users-fields" class="discount-type-fields mb-3" style="display: {{ old('type', $discount?->type) === 'new_users_only' ? 'block' : 'none' }};">
    <div class="form-check">
        <input type="hidden" name="new_users_only" value="0">
        <input type="checkbox" class="form-check-input" id="new_users_only" name="new_users_only" value="1"
            {{ old('new_users_only', $discount?->new_users_only) ? 'checked' : '' }}>
        <label class="form-check-label" for="new_users_only">{{ __('Apply only to new users (first purchase)') }}</label>
    </div>
</div>

<hr>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="discount_type" class="form-label">{{ __('Discount kind') }}</label>
        <select class="form-select @error('discount_type') is-invalid @enderror" id="discount_type" name="discount_type">
            <option value="percent" {{ old('discount_type', $discount?->discount_type ?? 'percent') === 'percent' ? 'selected' : '' }}>{{ __('livewire.percentage') }}</option>
            <option value="fixed" {{ old('discount_type', $discount?->discount_type) === 'fixed' ? 'selected' : '' }}>{{ __('Fixed amount') }}</option>
        </select>
        @error('discount_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="discount_value" class="form-label">{{ __('Discount value') }}</label>
        <input type="number" step="0.01" min="0" class="form-control @error('discount_value') is-invalid @enderror"
            id="discount_value" name="discount_value" value="{{ old('discount_value', $discount?->discount_value) }}" required>
        @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label for="min_price_until" class="form-label">{{ __('Minimum price (optional)') }}</label>
    <input type="number" step="0.01" min="0" class="form-control @error('min_price_until') is-invalid @enderror"
        id="min_price_until" name="min_price_until" value="{{ old('min_price_until', $discount?->min_price_until) }}"
        placeholder="{{ __('Do not reduce price below this') }}">
    <div class="form-text">{{ __('Deduct until the price does not go below this (e.g. floor price for Ramadan sale).') }}</div>
    @error('min_price_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-check mb-3">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
        {{ old('active', $discount?->active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">{{ __('Active') }}</label>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.getElementById('type');
    var dateRange = document.getElementById('date-range-fields');
    var hijri = document.getElementById('hijri-fields');
    var daysFromNow = document.getElementById('days-from-now-fields');
    var newUsers = document.getElementById('new-users-fields');

    function toggle() {
        var v = typeSelect.value;
        dateRange.style.display = v === 'date_range' ? 'block' : 'none';
        hijri.style.display = v === 'hijri_range' ? 'block' : 'none';
        daysFromNow.style.display = v === 'days_from_now' ? 'block' : 'none';
        newUsers.style.display = v === 'new_users_only' ? 'block' : 'none';
    }
    typeSelect.addEventListener('change', toggle);
    toggle();
});
</script>
