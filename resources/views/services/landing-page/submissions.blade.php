@extends('layouts.app')

@section('breadcrumbs')
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-end">
                </div>
                <h4 class="page-title">Form Submissions - {{ $service->title }}</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix row">
        <div class="col-lg-12">
            <div class="card bg-light">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-file-text me-2"></i>Form Submissions
                        <span class="badge bg-primary ms-2">{{ $submissions->total() }}</span>
                    </h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('services.landing-page.edit', $service) }}" class="btn btn-secondary btn-sm">
                            <i class="ti ti-arrow-left me-1"></i> Back to Landing Page
                        </a>
                        <a href="{{ route('services.mine') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-list me-1"></i> Back to Services
                        </a>
                        @if($submissions->count() > 0)
                            <a href="{{ route('services.landing-page.submissions.export', $service) }}"
                               class="btn btn-success btn-sm"
                               title="Export to CSV">
                                <i class="ti ti-download me-1"></i> Export CSV
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($submissions->count() > 0)
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form method="GET" action="{{ route('services.landing-page.submissions', $service) }}" id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="search" class="form-label small">Search</label>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="search"
                                               name="search"
                                               value="{{ request('search') }}"
                                               placeholder="Name, email, or phone...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label small">From Date</label>
                                        <input type="date"
                                               class="form-control form-control-sm"
                                               id="date_from"
                                               name="date_from"
                                               value="{{ request('date_from') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label small">To Date</label>
                                        <input type="date"
                                               class="form-control form-control-sm"
                                               id="date_to"
                                               name="date_to"
                                               value="{{ request('date_to') }}">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="w-100">
                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                <i class="ti ti-search me-1"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @if(request()->hasAny(['search', 'date_from', 'date_to']))
                                    <div class="mt-2">
                                        <a href="{{ route('services.landing-page.submissions', $service) }}" class="btn btn-link btn-sm p-0 text-decoration-none">
                                            <i class="ti ti-x me-1"></i> Clear filters
                                        </a>
                                    </div>
                                @endif
                            </form>
                        </div>

                        <!-- Submissions Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle submission-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 15%;">Submitted By</th>
                                        <th style="width: 20%;">Contact Info</th>
                                        <th style="width: 15%;">Answers</th>
                                        <th style="width: 15%;">Submitted At</th>
                                        <th style="width: 10%;">IP Address</th>
                                        <th style="width: 20%;" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submissions as $index => $submission)
                                        <tr>
                                            <td>{{ $submissions->firstItem() + $index }}</td>
                                            <td>
                                                <strong>{{ e($submission->submitted_by_name ?? 'N/A') }}</strong>
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    @if($submission->submitted_by_email)
                                                        <div>
                                                            <i class="ti ti-mail me-1"></i>
                                                            <a href="mailto:{{ e($submission->submitted_by_email) }}"
                                                               title="Send email to {{ e($submission->submitted_by_email) }}">
                                                                {{ e($submission->submitted_by_email) }}
                                                            </a>
                                                        </div>
                                                    @endif
                                                    @if($submission->submitted_by_phone)
                                                        <div class="mt-1">
                                                            <i class="ti ti-phone me-1"></i>
                                                            <a href="tel:{{ e($submission->submitted_by_phone) }}"
                                                               title="Call {{ e($submission->submitted_by_phone) }}">
                                                                {{ e($submission->submitted_by_phone) }}
                                                            </a>
                                                            @php
                                                                $whatsappLink = $submission->getWhatsAppLink();
                                                            @endphp
                                                            @if($whatsappLink)
                                                                <a href="{{ $whatsappLink }}"
                                                                   target="_blank" rel="noopener noreferrer"
                                                                   class="ms-2 btn btn-sm"
                                                                   style="background-color: #25D366; border-color: #25D366; color: white; padding: 0.15rem 0.5rem; font-size: 0.75rem;"
                                                                   title="Send WhatsApp message">
                                                                    <i class="ti ti-brand-whatsapp me-1"></i>WhatsApp
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if(!$submission->submitted_by_email && !$submission->submitted_by_phone)
                                                        <span class="text-muted small">{{ __('No contact info') }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-info"
                                                        data-premium-modal="landing-submission-details"
                                                        data-name="{{ e($submission->submitted_by_name ?? 'N/A') }}"
                                                        data-email="{{ e($submission->submitted_by_email ?? '') }}"
                                                        data-phone="{{ e($submission->submitted_by_phone ?? '') }}"
                                                        data-whatsapp="{{ $submission->getWhatsAppLink() ?? '' }}"
                                                        data-date="{{ $submission->created_at->format('F d, Y h:i A') }}"
                                                        data-ip="{{ e($submission->ip_address ?? 'N/A') }}"
                                                        data-user-agent="{{ e($submission->user_agent ?? 'N/A') }}"
                                                        data-form-data="{{ json_encode($submission->form_data ?? []) }}">
                                                    <i class="ti ti-eye me-1"></i> View Answers
                                                </button>
                                            </td>
                                            <td>
                                                <div>
                                                    <small class="text-muted d-block">
                                                        <i class="ti ti-calendar me-1"></i>
                                                        {{ $submission->created_at->format('M d, Y') }}
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="ti ti-clock me-1"></i>
                                                        {{ $submission->created_at->format('h:i A') }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <code class="small">{{ e($submission->ip_address ?? 'N/A') }}</code>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <div class="action-buttons">
                                                    @if($submission->submitted_by_email)
                                                        <a href="mailto:{{ e($submission->submitted_by_email) }}"
                                                           class="btn btn-sm btn-outline-primary"
                                                           title="Send email">
                                                            <i class="ti ti-mail"></i>
                                                        </a>
                                                    @endif
                                                    @if($submission->submitted_by_phone)
                                                        <a href="tel:{{ e($submission->submitted_by_phone) }}"
                                                           class="btn btn-sm btn-outline-success"
                                                           title="Call">
                                                            <i class="ti ti-phone"></i>
                                                        </a>
                                                        @php
                                                            $whatsappLink = $submission->getWhatsAppLink();
                                                        @endphp
                                                            @if($whatsappLink)
                                                                <a href="{{ $whatsappLink }}"
                                                                   target="_blank" rel="noopener noreferrer"
                                                                   class="btn btn-sm"
                                                                   style="background-color: #25D366; border-color: #25D366; color: white;"
                                                                   title="Send WhatsApp message">
                                                                    <i class="ti ti-brand-whatsapp"></i>
                                                                </a>
                                                            @endif
                                                    @endif
                                                    <form action="{{ route('services.landing-page.submissions.destroy', [$service, $submission]) }}"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirmDelete(this, '{{ e($submission->submitted_by_name ?? 'this submission') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="Delete submission">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($submissions->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $submissions->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <!-- Empty State -->
                        <div class="text-center empty-state">
                            <i class="ti ti-inbox empty-state-icon mb-3 d-block"></i>
                            <h5 class="text-muted mb-2">{{ __('No submissions yet') }}</h5>
                            <p class="text-muted mb-4">
                                @if(request()->hasAny(['search', 'date_from', 'date_to']))
                                    No submissions match your filters. Try adjusting your search criteria.
                                @else
                                    Form submissions will appear here once visitors submit your landing page form.
                                @endif
                            </p>
                            @if(request()->hasAny(['search', 'date_from', 'date_to']))
                                <a href="{{ route('services.landing-page.submissions', $service) }}" class="btn btn-outline-primary">
                                    <i class="ti ti-x me-2"></i> Clear Filters
                                </a>
                            @else
                                <a href="{{ route('services.landing-page.show', $landingPage->slug) }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-primary">
                                    <i class="ti ti-external-link me-2"></i>View Landing Page
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    // Confirm delete function
    function confirmDelete(form, submissionName) {
        if (confirm('Are you sure you want to delete the submission from "' + submissionName + '"? This action cannot be undone.')) {
            return true;
        }
        return false;
    }
</script>
@endpush
