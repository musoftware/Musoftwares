<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('general.more_actions') }}">
        <i class="ti ti-dots"></i>
        <span class="d-none d-xl-inline ms-1">{{ __('general.more') }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        {{-- Common actions --}}
        <li><a class="dropdown-item" href="{{ route('services.saved-replies', $service) }}">
            <i class="ti ti-message me-2"></i>{{ __('general.saved_replies') }}</a></li>
        <li><hr class="dropdown-divider"></li>

        {{-- Landing Page Section --}}
        <li class="dropdown-header">
            <i class="ti ti-world me-2"></i>{{ __('general.landing_page') }}
            @if($service->landingPage)
                @if($service->landingPage->is_active)
                    <span class="badge bg-success ms-2">{{ __('general.active') }}</span>
                @else
                    <span class="badge bg-warning ms-2">{{ __('general.inactive') }}</span>
                @endif
            @endif
        </li>
        @if($service->landingPage)
            <li>
                <a class="dropdown-item" href="{{ route('services.landing-page.show', $service->landingPage->slug) }}" target="_blank">
                    <i class="ti ti-external-link me-2"></i>{{ __('general.view_landing_page') }}</a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('services.landing-page.edit', $service) }}">
                    <i class="ti ti-edit me-2"></i>{{ __('general.edit_landing_page') }}</a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('services.landing-page.submissions', $service) }}">
                    <i class="ti ti-file-text me-2"></i>{{ __('general.form_submissions') }}
                    @php
                        $submissionCount = $service->landingPage->formSubmissions()->count();
                    @endphp
                    @if($submissionCount > 0)
                        <span class="badge bg-primary rounded-pill ms-2">{{ $submissionCount }}</span>
                    @endif
                </a>
            </li>
        @else
            <li>
                <a class="dropdown-item" href="{{ route('services.landing-page.create', $service) }}">
                    <i class="ti ti-plus me-2"></i>{{ __('general.create_landing_page') }}</a>
            </li>
        @endif

        {{-- Admin Actions --}}
        @if (Auth::user()->hasRole('admin'))
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('admin.services.activate', $service) }}">
                <i class="ti ti-check me-2"></i>{{ __('general.activate') }}
            </a></li>
            @if (!$service->user->hasRole('admin'))
                <li>
                    <a class="dropdown-item text-warning" href="javascript:void(0)" onclick="event.preventDefault(); if (confirm('{{ __('general.confirm_deactivate') }}')) document.getElementById('deactivate-form-{{ $service->id }}').submit();">
                        <i class="ti ti-ban me-2"></i>{{ __('general.deactivate') }}
                    </a>
                    <form id="deactivate-form-{{ $service->id }}" method="POST" action="{{ route('admin.services.update_status', $service) }}" class="d-none">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="declined">
                    </form>
                </li>
            @endif
        @endif

        {{-- Delete Action --}}
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="event.preventDefault(); if (confirm('{{ __('general.confirm_remove') }}')) document.getElementById('delete-form-{{ $service->id }}').submit();">
                <i class="ti ti-trash me-2"></i>{{ __('general.remove') }}
            </a>
            <form id="delete-form-{{ $service->id }}" method="POST" action="{{ route('services.destroy', $service) }}" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </li>
    </ul>
</div>
