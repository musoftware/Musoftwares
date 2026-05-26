@extends('layouts.app')

@section('title', 'Service History - ' . $service->title)

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header 
            icon="ti ti-history" 
            title="Service History" 
            subtitle="View changes made to {{ $service->title }}">
            <a href="{{ route('services.mine') }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </x-client.section-header>

        <div class="at-card admin-table-card">
            <div class="at-card-body p-0">
                <div class="table-responsive">
                    <table class="at-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Changed By</th>
                                <th>Title</th>
                                <th>Price</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($histories as $history)
                                <tr>
                                    <td data-label="Date">
                                        <div class="text-dark fw-semibold">{{ $history->created_at->format('M d, Y') }}</div>
                                        <div class="text-muted small">{{ $history->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td data-label="Changed By">
                                        {{ $history->user->name }}
                                    </td>
                                    <td data-label="Title">
                                        {{ $history->title }}
                                    </td>
                                    <td data-label="Price">
                                        {{ $history->price }}
                                    </td>
                                    <td data-label="Reason">
                                        <span class="text-muted italic">{{ $history->change_reason ?? 'N/A' }}</span>
                                    </td>
                                    <td data-label="Action">
                                        <button class="at-btn at-btn-ghost at-btn-sm text-primary" onclick="restoreVersion({{ $history->id }})">
                                            <i class="ti ti-restore me-1"></i> Restore
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <form id="restore-form" method="POST" style="display: none;">
                        @csrf
                    </form>

                    @if($histories->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-history text-muted display-1"></i>
                            <h4 class="mt-3">{{ __('No history found') }}</h4>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

<script>
function restoreVersion(historyId) {
    if (confirm("{{ __('Are you sure you want to restore this version? Current title and price will be saved as a new history record.') }}")) {
        const form = document.getElementById('restore-form');
        form.action = `/services/{{ $service->id }}/history/${historyId}/restore`;
        form.submit();
    }
}
</script>
@endsection
