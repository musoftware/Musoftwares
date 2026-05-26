@extends('layouts.app')

@section('title', 'Manage Serials')

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header
            icon="ti ti-key"
            title="{{ __('common.manage_serials') }}"
            subtitle="{{ __('Manage serial numbers for') }} {{ $service->title }}">
            <a href="{{ route('services.show', $service) }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Service') }}
            </a>
            <button type="button" class="at-btn at-btn-primary at-btn-sm" data-bs-toggle="modal" data-bs-target="#addSerialsModal" aria-label="Add">
                <i class="ti ti-plus me-1"></i>{{ __('Add Serials') }}
            </button>
        </x-client.section-header>

        <div class="at-card" style="border-radius: 8px;">
            <div class="at-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3">Serial</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Order ID</th>
                                <th class="py-3">Expires At</th>
                                <th class="py-3">Sold At</th>
                                <th class="text-end px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($serials as $serial)
                                <tr>
                                    <td class="px-4">
                                        <div class="font-monospace">{{ $serial->serial }}</div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $serial->status === 'available' ? 'bg-success' : ($serial->status === 'sold' ? 'bg-danger' : 'bg-warning') }}">
                                            {{ ucfirst($serial->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($serial->order_id)
                                            <a href="{{ route('orders.show', $serial->order_id) }}">#{{ $serial->order_id }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($serial->expires_at)
                                            {{ $serial->expires_at->format('Y-m-d') }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($serial->sold_at)
                                            {{ $serial->sold_at->format('Y-m-d H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border-0" type="button"
                                                data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('services.serials.edit', [$service, $serial]) }}">
                                                        <i class="ti ti-edit me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('services.serials.destroy', [$service, $serial]) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this serial?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="ti ti-trash me-2"></i>Delete
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
                                        <i class="ti ti-database-off fs-1 mb-3 d-block"></i>
                                        No serial numbers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($serials->hasPages())
                <div class="card-footer bg-white border-top-0 py-3">
                    {{ $serials->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Add Serials Modal -->
    <div class="modal fade" id="addSerialsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('services.serials.store', $service) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Serials</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="serials" class="form-label">Serial Numbers (One per line)</label>
                            <textarea class="form-control" id="serials" name="serials" rows="10" required
                                placeholder="XXXX-XXXX-XXXX-XXXX"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="expires_at" class="form-label">Expiration Date (Optional)</label>
                            <input type="date" class="form-control" id="expires_at" name="expires_at">
                            <div class="form-text">Leave blank if these serials do not expire.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Serials</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection