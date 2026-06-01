@extends('layouts.app')

@section('title', __('services.create.page_title'))

@section('content')
    <div class="dashboard-container at-mobile-scroll-fix service-wizard-container">
        <x-client.section-header 
            icon="ti ti-plus" 
            title="{{ __('services.create.title') }}" 
            subtitle="{{ __('services.create.subtitle') }}">
            <a href="{{ route('services.mine') }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('services.back_to_services') }}
            </a>
        </x-client.section-header>

        <div class="row">
            <div class="col-lg-8">
                {{-- Stepper --}}
                <div class="wizard-stepper" id="serviceWizardStepper">
                    <div class="step-item active" data-step="1">
                        <div class="step-number">
                            <span>1</span>
                            <i class="ti ti-check"></i>
                        </div>
                        <div class="step-title">{{ __('services.create.steps.basic_info') }}</div>
                    </div>
                    <div class="step-item" data-step="2">
                        <div class="step-number">
                            <span>2</span>
                            <i class="ti ti-check"></i>
                        </div>
                        <div class="step-title">{{ __('services.create.steps.description') }}</div>
                    </div>
                    <div class="step-item" data-step="3">
                        <div class="step-number">
                            <span>3</span>
                            <i class="ti ti-check"></i>
                        </div>
                        <div class="step-title">{{ __('services.create.steps.pricing') }}</div>
                    </div>
                    <div class="step-item" data-step="4">
                        <div class="step-number">
                            <span>4</span>
                            <i class="ti ti-check"></i>
                        </div>
                        <div class="step-title">{{ __('services.create.steps.media') }}</div>
                    </div>
                    <div class="step-item" data-step="5">
                        <div class="step-number">
                            <span>5</span>
                            <i class="ti ti-check"></i>
                        </div>
                        <div class="step-title">{{ __('services.create.steps.publish') }}</div>
                    </div>
                </div>

                <form action="{{ route('services.store') }}" method="POST" enctype="multipart/form-data" id="serviceWizardForm">
                    @csrf

                    {{-- Step 1: Basic Info --}}
                    <div class="wizard-step-content active" data-step="1">
                        <x-client.form-card icon="info-circle" iconClass="text-primary" title="{{ __('services.create.steps.basic_info') }}">
                            <div class="d-flex justify-content-end mb-3">
                                <button type="button" class="at-btn at-btn-primary at-btn-sm" data-bs-toggle="modal" data-bs-target="#aiAutoFillModal">
                                    <i class="ti ti-wand me-2"></i>{{ __('services.ai.auto_fill_with_ai') }}
                                </button>
                            </div>

                            <div class="mb-4">
                                <label for="service_category_id" class="form-label fw-bold">{{ __('common.category') }} <span class="text-danger">*</span></label>
                                <select name="service_category_id" id="service_category_id" class="form-select form-select-lg" required>
                                    <option value="" disabled selected>{{ __('services.form.select_category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="title_en" class="form-label fw-bold">{{ __('services.form.title_english') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="title_en" name="title_en" class="form-control" value="{{ old('title_en') }}" placeholder="{{ __('services.placeholders.title_en') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="title_ar" class="form-label fw-bold">{{ __('services.form.title_arabic') }}</label>
                                    <input type="text" id="title_ar" name="title_ar" class="form-control" dir="rtl" value="{{ old('title_ar') }}" placeholder="{{ __('services.placeholders.title_ar') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="tagline_en" class="form-label fw-bold">{{ __('services.form.tagline_english') }}</label>
                                    <input type="text" id="tagline_en" name="tagline_en" class="form-control" value="{{ old('tagline_en') }}" placeholder="{{ __('services.placeholders.tagline_en') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="tagline_ar" class="form-label fw-bold">{{ __('services.form.tagline_arabic') }}</label>
                                    <input type="text" id="tagline_ar" name="tagline_ar" class="form-control" dir="rtl" value="{{ old('tagline_ar') }}" placeholder="{{ __('services.placeholders.tagline_ar') }}">
                                </div>
                                <div class="col-12">
                                    <label for="tags" class="form-label fw-bold">{{ __('services.form.tags') }}</label>
                                    <div class="tags-input-container">
                                        <div class="tags-display" id="tagsDisplay">
                                            @if(old('tags'))
                                                @foreach(json_decode(old('tags'), true) as $tag)
                                                    <span class="tag-badge">{{ $tag }} <button type="button" class="tag-remove" onclick="removeTag(this)">×</button></span>
                                                @endforeach
                                            @endif
                                        </div>
                                        <input type="text" id="tagsInput" class="form-control" placeholder="{{ __('services.form.tags_placeholder') }}">
                                        <input type="hidden" name="tags" id="tagsHidden" value="{{ old('tags') ?? '[]' }}">
                                    </div>
                                    <div class="form-text">{{ __('services.form.tags_help') }}</div>

                                    <div class="mt-3">
                                        <label for="service_link" class="form-label fw-bold">{{ __('services.form.original_service_link_optional') }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted border-end-0">
                                                <i class="ti ti-link"></i>
                                            </span>
                                            <input type="url" id="service_link" name="service_link"
                                                class="form-control border-start-0 ps-0" value="{{ old('service_link') }}"
                                                placeholder="{{ __('services.placeholders.original_service_link') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </x-client.form-card>
                    </div>

                    {{-- Step 2: Description --}}
                    <div class="wizard-step-content" data-step="2">
                        <x-client.form-card icon="align-left" iconClass="text-primary" title="{{ __('services.create.steps.description') }}">
                            <div class="mb-4">
                                <label for="description_en" class="form-label fw-bold">{{ __('services.form.description_english') }} <span class="text-danger">*</span></label>
                                <textarea name="description_en" id="description_en" class="form-control" rows="8" placeholder="{{ __('services.placeholders.description_en') }}" required>{{ old('description_en') }}</textarea>
                            </div>
                            <div class="mb-4">
                                <label for="description_ar" class="form-label fw-bold">{{ __('services.form.description_arabic') }}</label>
                                <textarea name="description_ar" id="description_ar" class="form-control" rows="8" dir="rtl" placeholder="{{ __('services.placeholders.description_ar') }}">{{ old('description_ar') }}</textarea>
                            </div>
                            <div class="mb-0">
                                <label for="auto_reply_en" class="form-label fw-bold">{{ __('services.form.auto_reply_message_optional') }}</label>
                                <textarea name="auto_reply_en" id="auto_reply_en" class="form-control" rows="3" placeholder="{{ __('services.placeholders.auto_reply_en') }}">{{ old('auto_reply_en') }}</textarea>
                                <div class="form-text">{{ __('services.form.auto_reply_help_text') }}</div>
                            </div>
                        </x-client.form-card>
                    </div>

                    {{-- Step 3: Pricing & Extras --}}
                    <div class="wizard-step-content" data-step="3">
                        <x-client.form-card icon="coin" iconClass="text-success" title="{{ __('services.create.steps.pricing') }}">
                            {{-- Pricing Tiers --}}
                            <div class="pricing-tiers mb-4">
                                <h6 class="fw-bold mb-3">{{ __('services.form.pricing_packages') }}</h6>
                                 
                                <!-- Package Tabs -->
                                <ul class="nav nav-pills mb-3" id="packageTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                            {{ __('Basic') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="standard-tab" data-bs-toggle="tab" data-bs-target="#standard" type="button" role="tab">
                                            {{ __('Standard') }}
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="premium-tab" data-bs-toggle="tab" data-bs-target="#premium" type="button" role="tab">
                                            {{ __('Premium') }}
                                        </button>
                                    </li>
                                </ul>
                                 
                                <!-- Package Content -->
                                <div class="tab-content" id="packageTabContent">
                                    <!-- Basic Package -->
                                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                        <div class="package-form">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Package Name') }}</label>
                                                    <input type="text" name="packages[basic][name]" class="form-control" value="Basic" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Price') }} <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="packages[basic][price]" class="form-control" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Delivery Days') }} <span class="text-danger">*</span></label>
                                                    <input type="number" name="packages[basic][delivery_days]" class="form-control" min="1" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Revisions') }}</label>
                                                    <input type="text" name="packages[basic][revisions]" class="form-control" placeholder="2 or Unlimited">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('Description') }}</label>
                                                    <textarea name="packages[basic][description]" class="form-control" rows="2" placeholder="{{ __('Describe what\'s included in this package') }}"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('Features') }}</label>
                                                    <div id="basic-features" class="features-list">
                                                        <div class="feature-item row g-2 mb-2">
                                                            <div class="col-md-10">
                                                                <input type="text" name="packages[basic][features][]" class="form-control" placeholder="{{ __('Enter a feature') }}">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" class="btn btn-sm btn-outline-danger remove-feature" onclick="this.closest('.feature-item').remove()">
                                                                    <i class="ti ti-x"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFeature('basic')">
                                                        <i class="ti ti-plus me-1"></i>{{ __('Add Feature') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Standard Package -->
                                    <div class="tab-pane fade" id="standard" role="tabpanel">
                                        <div class="package-form">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Package Name') }}</label>
                                                    <input type="text" name="packages[standard][name]" class="form-control" value="Standard" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Price') }} <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="packages[standard][price]" class="form-control" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Delivery Days') }} <span class="text-danger">*</span></label>
                                                    <input type="number" name="packages[standard][delivery_days]" class="form-control" min="1" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Revisions') }}</label>
                                                    <input type="text" name="packages[standard][revisions]" class="form-control" placeholder="3 or Unlimited">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('Description') }}</label>
                                                    <textarea name="packages[standard][description]" class="form-control" rows="2" placeholder="{{ __('Describe what\'s included in this package') }}"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('Features') }}</label>
                                                    <div id="standard-features" class="features-list">
                                                        <div class="feature-item row g-2 mb-2">
                                                            <div class="col-md-10">
                                                                <input type="text" name="packages[standard][features][]" class="form-control" placeholder="{{ __('Enter a feature') }}">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" class="btn btn-sm btn-outline-danger remove-feature" onclick="this.closest('.feature-item').remove()">
                                                                    <i class="ti ti-x"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFeature('standard')">
                                                        <i class="ti ti-plus me-1"></i>{{ __('Add Feature') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Premium Package -->
                                    <div class="tab-pane fade" id="premium" role="tabpanel">
                                        <div class="package-form">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Package Name') }}</label>
                                                    <input type="text" name="packages[premium][name]" class="form-control" value="Premium" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Price') }} <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="packages[premium][price]" class="form-control" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Delivery Days') }} <span class="text-danger">*</span></label>
                                                    <input type="number" name="packages[premium][delivery_days]" class="form-control" min="1" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('Revisions') }}</label>
                                                    <input type="text" name="packages[premium][revisions]" class="form-control" placeholder="Unlimited">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('Description') }}</label>
                                                    <textarea name="packages[premium][description]" class="form-control" rows="2" placeholder="{{ __('Describe what\'s included in this package') }}"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('Features') }}</label>
                                                    <div id="premium-features" class="features-list">
                                                        <div class="feature-item row g-2 mb-2">
                                                            <div class="col-md-10">
                                                                <input type="text" name="packages[premium][features][]" class="form-control" placeholder="{{ __('Enter a feature') }}">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" class="btn btn-sm btn-outline-danger remove-feature" onclick="this.closest('.feature-item').remove()">
                                                                    <i class="ti ti-x"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFeature('premium')">
                                                        <i class="ti ti-plus me-1"></i>{{ __('Add Feature') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="at-btn at-btn-ghost at-btn-sm mt-2" id="btn-add-extra">
                                <i class="ti ti-plus me-1"></i> {{ __('services.form.add_service_extra') }}
                            </button>

                            <hr class="my-4">

                            {{-- Free Service Toggle --}}
                            <div class="card border-success border-opacity-25 bg-success bg-opacity-5 rounded-3 p-3 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_free" name="is_free" value="1"
                                           {{ old('is_free') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-success" for="is_free">
                                        <i class="ti ti-gift me-1"></i>{{ __('services.free.form_label') }}
                                    </label>
                                    <div class="form-text">{{ __('services.free.form_help') }}</div>
                                </div>

                                <div id="require_share_section" class="{{ old('is_free') ? '' : 'd-none' }} ps-4 border-start border-success border-opacity-25">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="require_share_to_download"
                                               name="require_share_to_download" value="1"
                                               {{ old('require_share_to_download') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="require_share_to_download">
                                            <i class="ti ti-brand-facebook me-1 text-primary"></i>{{ __('services.free.require_share_label') }}
                                        </label>
                                        <div class="form-text">{{ __('services.free.require_share_help') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="generate_serials" name="generate_serials" value="1" {{ old('generate_serials') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="generate_serials">{{ __('services.form.generate_serials') }}</label>
                                <div class="form-text">{{ __('services.form.generate_serials_help') }}</div>
                            </div>

                            <div id="serials_options" class="ps-4 border-start d-none">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="allow_random_serial" name="allow_random_serial" value="1" {{ old('allow_random_serial') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_random_serial"><strong>{{ __('services.form.allow_random_serials') }}</strong></label>
                                    <div class="form-text">{{ __('services.form.allow_random_serials_help') }}</div>
                                </div>
                                <div class="mb-3">
                                    <label for="validity_days" class="form-label"><strong>{{ __('services.form.validity_period_days') }}</strong></label>
                                    <input type="number" class="form-control" id="validity_days" name="validity_days" value="{{ old('validity_days') }}" min="1" placeholder="{{ __('services.placeholders.validity_days') }}">
                                </div>
                            </div>
                        </x-client.form-card>
                    </div>

                    {{-- Step 4: Media --}}
                    <div class="wizard-step-content" data-step="4">
                        <x-client.form-card icon="photo" iconClass="text-warning" title="{{ __('services.create.steps.media') }}">
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('services.form.main_image') }} <span class="text-danger">*</span></label>
                                <div class="dropzone-area" onclick="document.getElementById('image').click()">
                                    <i class="ti ti-cloud-upload"></i>
                                    <div class="dropzone-text">{{ __('general.click_or_drag_drop_to_upload_main_image') }}</div>
                                    <div class="dropzone-hint">{{ __('general.recommended_size_800x600px_max_5mb') }}</div>
                                    <input type="file" id="image" name="image" class="d-none" accept="image/*" required>
                                </div>
                                <div id="mainImagePreview" class="media-previews"></div>
                            </div>

                             <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('services.form.additional_images') }}</label>
                                <div class="dropzone-area" onclick="document.getElementById('images').click()">
                                    <i class="ti ti-photo-plus"></i>
                                    <div class="dropzone-text">{{ __('general.add_more_images_to_your_gallery') }}</div>
                                    <input type="file" id="images" name="images[]" class="d-none" accept="image/*" multiple>
                                </div>
                                <div id="galleryPreviews" class="media-previews"></div>
                            </div>

                            {{-- Digital Delivery (Service Files) --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('services.form.digital_delivery_files') }}</label>
                                <div class="dropzone-area" onclick="document.getElementById('service_files').click()">
                                    <i class="ti ti-file-upload"></i>
                                    <div class="dropzone-text">{{ __('services.form.upload_files_help') }}</div>
                                    <input type="file" id="service_files" name="service_files[]" class="d-none" multiple>
                                </div>
                                <div id="filePreviews" class="file-previews mt-3"></div>
                                <div class="form-text text-muted">
                                    <i class="ti ti-info-circle me-1"></i>{{ __('services.form.digital_delivery_note') }}
                                </div>
                            </div>

                            <div class="mb-0">
                                <label for="portfolio_link" class="form-label fw-bold">{{ __('services.form.portfolio_link') }}</label>
                                <input type="url" id="portfolio_link" name="portfolio_link" class="form-control" value="{{ old('portfolio_link') }}" placeholder="{{ __('services.placeholders.portfolio_link') }}">
                            </div>


                    {{-- Digital Deliverables --}}
                    <x-client.form-card icon="file-download" iconClass="text-info" title="{{ __('Digital Deliverables') }}" class="mb-5">
                        <p class="form-text mb-3">{{ __('Upload files that buyers will automatically receive after purchasing this service (e.g., templates, source code, documents).') }}</p>
                        
                        <div class="mb-2">
                            <label class="form-label fw-bold">{{ __('Service Files') }}</label>
                            <div class="image-upload-area" onclick="document.getElementById('service_files').click()" style="border: 2px dashed #ddd; border-radius: 8px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s ease; background: #f8f9fa;">
                                <div class="upload-content">
                                    <i class="ti ti-file-upload upload-icon" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                                    <div class="upload-text">
                                        <h6 style="margin-bottom: 0.5rem; color: #495057;">{{ __('Click or drag & drop to upload deliverable files') }}</h6>
                                        <p class="text-muted" style="margin-bottom: 0; font-size: 0.875rem;">{{ __('Max 50MB per file. Any file type accepted.') }}</p>
                                    </div>
                                </div>
                                <input type="file" id="service_files" name="service_files[]" class="d-none" multiple>
                            </div>
                            <div id="serviceFilePreviews" class="mt-2"></div>
                        </div>
                    </x-client.form-card>

                    {{-- Draft Notification Alert --}}
                    <div id="draftAlert" class="alert alert-info d-none mb-4 animate__animated animate__fadeIn">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-history fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">{{ __('Draft Found!') }}</h6>
                                    <p class="mb-0 small">{{ __('We found a saved draft for this service. Would you like to restore it?') }}</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary" id="btnRestoreDraft">{{ __('Restore') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearDraft">{{ __('Clear') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mb-5">
                        <a href="{{ route('services.mine') }}" class="btn btn-outline-secondary px-4">{{ __('common.cancel') }}</a>
                        <button type="submit" name="status" value="draft" class="btn btn-secondary px-4">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save as Draft') }}
                        </button>
                        <button type="submit" name="status" value="approved" class="at-btn at-btn-primary" aria-label="Confirm">
                            <i class="ti ti-circle-check me-1"></i>{{ __('services.form.submit') }}
                        </button>
                    </div>

                    {{-- Step 5: Publish --}}
                    <div class="wizard-step-content" data-step="5">
                        <x-client.form-card icon="rocket" iconClass="text-success" title="{{ __('services.create.steps.publish') }}">
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="ti ti-confetti text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h4>{{ __('general.ready_to_go_live') }}</h4>
                                <p class="text-muted mb-4">{{ __('general.please_review_your_service_details_on_the_right_before_publishing') }}</p>
                                
                                <div class="alert alert-info d-flex align-items-center text-start">
                                    <i class="ti ti-info-circle me-3 fs-3"></i>
                                    <div>{{ __('general.once_published_your_service_will_be_visible_to_everyone_you_can_always_edit_it_later_or_mark_it_as_inactive') }}</div>
                                </div>
                            </div>
                        </x-client.form-card>
                    </div>

                    {{-- Wizard Navigation --}}
                    <div class="wizard-navigation">
                        <button type="button" class="at-btn at-btn-ghost d-none" id="btnPrev">
                            <i class="ti ti-arrow-left me-2"></i> {{ __('services.create.wizard.previous') }}
                        </button>
                        <div class="ms-auto d-flex gap-2">
                            <span id="autosaveBadge" class="at-badge at-badge-success d-none">
                                <i class="ti ti-device-floppy me-1"></i> {{ __('services.create.wizard.autosaved') }}
                            </span>
                            <button type="button" class="at-btn at-btn-primary" id="btnNext">
                                {{ __('services.create.wizard.next') }} <i class="ti ti-arrow-right ms-2"></i>
                            </button>
                            <button type="submit" class="at-btn at-btn-primary d-none" id="btnSubmit">
                                <i class="ti ti-check me-2"></i> {{ __('services.create.submit') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Sidebar Preview --}}
            <div class="col-lg-4">
                <div class="preview-sidebar">
                    <h5 class="mb-3 fw-bold">{{ __('services.create.wizard.preview') }}</h5>
                    <div class="live-preview-card">
                        <div class="preview-image" id="cardPreviewImage">
                            <i class="ti ti-photo"></i>
                        </div>
                        <div class="preview-body">
                            <div class="preview-category" id="cardPreviewCategory">Category</div>
                            <h3 class="preview-title" id="cardPreviewTitle">{{ __('general.service_title_1') }}</h3>
                            
                            <div class="preview-footer">
                                <div class="preview-price">
                                    <span id="cardPreviewCurrency">$</span><span id="cardPreviewPrice">0.00</span>
                                </div>
                                <div class="preview-rating">
                                    <i class="ti ti-star-filled"></i> 5.0 (0)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="at-card mt-4 p-3 bg-light border-0">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ti ti-bulb text-warning fs-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark small">{{ __('general.pro_tip') }}</div>
                                <div class="text-muted extra-small">{{ __('services.tip.create_help_text') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Modal --}}
    <div class="modal fade" id="aiAutoFillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('services.ai.auto_fill_with_ai') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="aiPrompt" class="form-label">{{ __('services.ai.describe_your_service') }}</label>
                        <textarea class="form-control" id="aiPrompt" rows="3" placeholder="{{ __('services.ai.prompt_placeholder') }}"></textarea>
                    </div>
                    <div id="aiError" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="at-btn at-btn-ghost" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="button" class="at-btn at-btn-primary" id="btnGenerateAI">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                        {{ __('common.generate') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 5;
    const form = document.getElementById('serviceWizardForm');
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnSubmit = document.getElementById('btnSubmit');
    const autosaveBadge = document.getElementById('autosaveBadge');

    // Wizard Logic
    function updateWizard() {
        // Update content
        document.querySelectorAll('.wizard-step-content').forEach(content => {
            content.classList.remove('active');
            if (content.dataset.step == currentStep) content.classList.add('active');
        });

        // Update stepper
        document.querySelectorAll('.wizard-stepper .step-item').forEach(item => {
            const step = parseInt(item.dataset.step);
            item.classList.remove('active', 'completed');
            if (step === currentStep) item.classList.add('active');
            else if (step < currentStep) item.classList.add('completed');
        });

        // Update buttons
        btnPrev.classList.toggle('d-none', currentStep === 1);
        if (currentStep === totalSteps) {
            btnNext.classList.add('d-none');
            btnSubmit.classList.remove('d-none');
        } else {
            btnNext.classList.remove('d-none');
            btnSubmit.classList.add('d-none');
        }

        // Scroll to top of wizard
        window.scrollTo({ top: document.querySelector('.service-wizard-container').offsetTop - 20, behavior: 'smooth' });
    }

    btnNext.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            currentStep++;
            updateWizard();
            saveDraft();
        }
    });

    btnPrev.addEventListener('click', () => {
        currentStep--;
        updateWizard();
    });

    function validateStep(step) {
        const currentContent = document.querySelector(`.wizard-step-content[data-step="${step}"]`);
        const requiredFields = currentContent.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert("{{ __('services.create.wizard.required_fields') }}");
        }
        return isValid;
    }

    // Live Preview Logic
    const titleEn = document.getElementById('title_en');
    const categorySelect = document.getElementById('service_category_id');
    const priceInput = document.getElementById('price');
    const currencySelect = document.getElementById('currency');
    const imageInput = document.getElementById('image');

    const previewTitle = document.getElementById('cardPreviewTitle');
    const previewCategory = document.getElementById('cardPreviewCategory');
    const previewPrice = document.getElementById('cardPreviewPrice');
    const previewCurrency = document.getElementById('cardPreviewCurrency');
    const previewImageContainer = document.getElementById('cardPreviewImage');

    function updatePreview() {
        previewTitle.textContent = titleEn.value || 'Service Title...';
        previewCategory.textContent = categorySelect.options[categorySelect.selectedIndex]?.text || 'Category';
        previewPrice.textContent = parseFloat(priceInput.value || 0).toFixed(2);
        previewCurrency.textContent = currencySelect.options[currencySelect.selectedIndex]?.text.trim() || '$';
    }

    [titleEn, categorySelect, priceInput, currencySelect].forEach(el => {
        el.addEventListener('input', updatePreview);
    });

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                previewImageContainer.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                
                // Also update the preview area in Step 4
                document.getElementById('mainImagePreview').innerHTML = `
                    <div class="media-item">
                        <img src="${event.target.result}" alt="Main service image preview">
                        <button type="button" class="remove-btn" onclick="removeMainImage()"><i class="ti ti-x"></i></button>
                    </div>
                `;
            }
            reader.readAsDataURL(file);
        }
    });

    window.removeMainImage = function() {
        imageInput.value = '';
        previewImageContainer.innerHTML = '<i class="ti ti-photo"></i>';
        document.getElementById('mainImagePreview').innerHTML = '';
    }

    // Gallery Previews
    document.getElementById('images').addEventListener('change', function(e) {
        const container = document.getElementById('galleryPreviews');
        container.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.className = 'media-item';
                div.innerHTML = `<img src="${event.target.result}" alt="Gallery image preview">`;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    });

    // Service Files Previews
    const serviceFilesInput = document.getElementById('service_files');
    if (serviceFilesInput) {
        serviceFilesInput.addEventListener('change', function(e) {
            const container = document.getElementById('serviceFilePreviews');
            container.innerHTML = '';
            Array.from(e.target.files).forEach((file, index) => {
                if (file.size > 50 * 1024 * 1024) {
                    alert(`File "${file.name}" exceeds 50MB limit`);
                    return;
                }
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center p-2 bg-light rounded mb-1';
                div.innerHTML = `
                    <div>
                        <i class="ti ti-file me-2"></i>
                        <span>${file.name}</span>
                        <span class="text-muted small ms-2">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                    </div>
                `;
                container.appendChild(div);
            });

            // Dynamic FAQs
            const faqsContainer = document.getElementById('faqs-container');
            let faqCount = 0;

            function addFaqRow() {
                const row = document.createElement('div');
                row.className = 'row g-3 mb-3 pb-3 border-bottom faq-row';
                row.innerHTML = `
                            <div class="col-md-5">
                                <input type="text" name="faqs[${faqCount}][question]" class="form-control" placeholder="Question" required>
                            </div>
                            <div class="col-md-5">
                                <textarea name="faqs[${faqCount}][answer]" class="form-control" rows="1" placeholder="Answer" required></textarea>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.faq-row').remove()" aria-label="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        `;
                faqsContainer.appendChild(row);
                faqCount++;
            }

            document.getElementById('btn-add-faq').addEventListener('click', addFaqRow);

            // Dynamic Extras
            const extrasContainer = document.getElementById('extras-container');
            let extraCount = 0;

            function getCurrencySymbol() {
                const currencySelect = document.getElementById('currency');
                if (currencySelect && currencySelect.selectedIndex >= 0) {
                    const text = currencySelect.options[currencySelect.selectedIndex].text.trim();
                    return text === 'Select' ? '$' : text;
                }
                return '$';
            }

            function updateExtraCurrencySymbols() {
                const symbol = getCurrencySymbol();
                document.querySelectorAll('.extra-currency-symbol').forEach(el => {
                    el.textContent = symbol;
                });
            }

            if (document.getElementById('currency')) {
                document.getElementById('currency').addEventListener('change', updateExtraCurrencySymbols);
            }

            function addExtraRow() {
                const row = document.createElement('div');
                row.className = 'row g-3 mb-3 pb-3 border-bottom extra-row';
                row.innerHTML = `
                            <div class="col-md-4">
                                <input type="text" name="extras[${extraCount}][title]" class="form-control" placeholder="{{ __('general.title_e_g_fast_delivery') }}" required>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                     <span class="input-group-text extra-currency-symbol">${getCurrencySymbol()}</span>
                                     <input type="number" name="extras[${extraCount}][price]" class="form-control" placeholder="Price" step="0.01" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="extras[${extraCount}][duration_days]" class="form-control" placeholder="{{ __('general.extra_days') }}" min="0">
                                <div class="form-text mt-0" style="font-size: 0.75rem;">{{ __('general.duration_added_to_delivery') }}</div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.extra-row').remove()" aria-label="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        `;
                extrasContainer.appendChild(row);
                extraCount++;
            }

            document.getElementById('btn-add-extra').addEventListener('click', addExtraRow);

            // --- Free Service Logic ---
            const isFreeCheckbox = document.getElementById('is_free');
            const requireShareSection = document.getElementById('require_share_section');
            const priceInput = document.getElementById('price');

            function applyFreeServiceState(isFree) {
                if (requireShareSection) {
                    requireShareSection.classList.toggle('d-none', !isFree);
                }
                if (priceInput) {
                    if (isFree) {
                        priceInput.value = '0';
                        priceInput.min = '0';
                        priceInput.setAttribute('readonly', true);
                    } else {
                        priceInput.removeAttribute('readonly');
                        priceInput.min = '0';
                        if (priceInput.value === '0' || priceInput.value === '0.00') {
                            priceInput.value = '';
                        }
                    }
                }
            }

            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    if (parseFloat(this.value) === 0 && isFreeCheckbox && !isFreeCheckbox.checked) {
                        isFreeCheckbox.checked = true;
                        applyFreeServiceState(true);
                    }
                });
            }

            if (isFreeCheckbox) {
                isFreeCheckbox.addEventListener('change', function() {
                    applyFreeServiceState(this.checked);
                });
                // Apply on load
                applyFreeServiceState(isFreeCheckbox.checked);
            }

            // --- Draft Support (Autosave) ---
            const DRAFT_KEY = 'service_creation_draft';
            const serviceForm = document.getElementById('serviceForm');
            const draftAlert = document.getElementById('draftAlert');
            const btnRestoreDraft = document.getElementById('btnRestoreDraft');
            const btnClearDraft = document.getElementById('btnClearDraft');

            function saveDraft() {
                const formData = new FormData(serviceForm);
                const data = {};
                formData.forEach((value, key) => {
                    // Don't save files
                    if (value instanceof File) return;
                    
                    if (key.includes('[')) {
                        // Handle array fields (FAQs, Extras)
                        if (!data[key]) data[key] = [];
                        data[key] = value;
                    } else {
                        data[key] = value;
                    }
                });
                
                // Also save dynamic counts
                data._faqCount = faqCount;
                data._extraCount = extraCount;
                data._timestamp = new Date().getTime();

                localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
            }

            function restoreDraft() {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;

                const data = JSON.parse(raw);
                
                // 1. Restore dynamic rows first
                if (data._faqCount > 0) {
                    for (let i = 0; i < data._faqCount; i++) addFaqRow();
                }
                if (data._extraCount > 0) {
                    for (let i = 0; i < data._extraCount; i++) addExtraRow();
                }

                // 2. Restore all fields
                Object.keys(data).forEach(key => {
                    if (key.startsWith('_')) return;
                    
                    const element = serviceForm.elements[key];
                    if (!element) return;

                    if (element.type === 'checkbox') {
                        element.checked = data[key] === '1' || data[key] === 'on';
                    } else if (element.type === 'radio') {
                        if (element.value === data[key]) element.checked = true;
                    } else {
                        element.value = data[key];
                    }
                });

                // Trigger dependent logic
                if (isFreeCheckbox) applyFreeServiceState(isFreeCheckbox.checked);
                if (generateSerialsCheckbox) toggleRandomSerial();
                updateExtraCurrencySymbols();

                draftAlert.classList.add('d-none');
            }

            function checkDraft() {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (raw) {
                    const data = JSON.parse(raw);
                    const age = (new Date().getTime() - data._timestamp) / 1000;
                    
                    // Only show if draft is relatively fresh (e.g. less than 7 days)
                    if (age < 7 * 24 * 3600) {
                        draftAlert.classList.remove('d-none');
                    }
                }
            }

            // Listen for changes
            serviceForm.querySelectorAll('input, textarea, select').forEach(input => {
                input.addEventListener('input', debounce(saveDraft, 1000));
            });

            // Handle Draft Actions
            btnRestoreDraft.addEventListener('click', restoreDraft);
            btnClearDraft.addEventListener('click', () => {
                localStorage.removeItem(DRAFT_KEY);
                draftAlert.classList.add('d-none');
            });

            // Clear draft on successful submit
            serviceForm.addEventListener('submit', () => {
                localStorage.removeItem(DRAFT_KEY);
            });

            // Helper: Debounce
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Initial Check
            checkDraft();

            // File selection preview (Service Files)
            const serviceFileInput = document.getElementById('service_files');
            const serviceFilePreviews = document.getElementById('serviceFilePreviews');
            
            if (serviceFileInput) {
                serviceFileInput.addEventListener('change', function() {
                    serviceFilePreviews.innerHTML = '';
                    Array.from(this.files).forEach(file => {
                        const div = document.createElement('div');
                        div.className = 'd-flex align-items-center p-2 bg-light rounded mb-1';
                        div.innerHTML = `
                            <i class="ti ti-file me-2"></i>
                            <span class="small">${file.name}</span>
                            <span class="ms-auto text-muted small">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                        `;
                        serviceFilePreviews.appendChild(div);
                    });
                });
            }
        });
    }

    // Tags functionality
    const tagsInput = document.getElementById('tagsInput');
    const tagsDisplay = document.getElementById('tagsDisplay');
    const tagsHidden = document.getElementById('tagsHidden');
    let tags = JSON.parse(tagsHidden.value || '[]');

    function updateTagsDisplay() {
        tagsDisplay.innerHTML = '';
        tags.forEach(tag => {
            const tagElement = document.createElement('span');
            tagElement.className = 'tag-badge';
            tagElement.innerHTML = `${tag} <button type="button" class="tag-remove" onclick="removeTag(this)">×</button>`;
            tagsDisplay.appendChild(tagElement);
        });
        tagsHidden.value = JSON.stringify(tags);
    }

    function addTag(tag) {
        if (tag && !tags.includes(tag)) {
            tags.push(tag);
            updateTagsDisplay();
        }
    }

    function removeTag(button) {
        const tagText = button.parentElement.textContent.replace('×', '').trim();
        tags = tags.filter(tag => tag !== tagText);
        updateTagsDisplay();
    }

    tagsInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const tag = this.value.trim();
            if (tag) {
                addTag(tag);
                this.value = '';
            }
        }
    });

    // Features functionality
    window.addFeature = function(packageType) {
        const featuresContainer = document.getElementById(`${packageType}-features`);
        const newFeature = document.createElement('div');
        newFeature.className = 'feature-item row g-2 mb-2';
        newFeature.innerHTML = `
            <div class="col-md-10">
                <input type="text" name="packages[${packageType}][features][]" class="form-control" placeholder="${__('Enter a feature')}">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger remove-feature" onclick="this.closest('.feature-item').remove()">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        featuresContainer.appendChild(newFeature);
    };

    // Smart Pricing Suggestions Mockup
    categorySelect.addEventListener('change', function() {
        const suggestionBox = document.getElementById('pricingSuggestions');
        suggestionBox.classList.remove('d-none');
        
        // Mock data logic based on category ID
        const catId = this.value;
        const base = (catId * 10) + 5;
        document.getElementById('minPrice').textContent = base.toFixed(2);
        document.getElementById('avgPrice').textContent = (base * 2.5).toFixed(2);
        document.getElementById('maxPrice').textContent = (base * 5.2).toFixed(2);
    });

    // Extras Logic
    const extrasContainer = document.getElementById('extras-container');
    let extraCount = 0;

    function addExtraRow() {
        const div = document.createElement('div');
        div.className = 'at-card mb-3 p-3 position-relative border-light';
        div.innerHTML = `
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="this.closest('.at-card').remove()"></button>
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="extras[${extraCount}][title]" class="form-control" placeholder="{{ __('general.title_e_g_fast_delivery') }}" required>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="extras[${extraCount}][price]" class="form-control" placeholder="Price" step="0.01" min="1" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="number" name="extras[${extraCount}][duration_days]" class="form-control" placeholder="{{ __('general.extra_days') }}" min="0">
                </div>
            </div>
        `;
        extrasContainer.appendChild(div);
        extraCount++;
    }
    document.getElementById('btn-add-extra').addEventListener('click', addExtraRow);

    // Serials Toggle
    document.getElementById('generate_serials').addEventListener('change', function() {
        document.getElementById('serials_options').classList.toggle('d-none', !this.checked);
    });

    // Free Service Toggle
    const isFreeCheckbox = document.getElementById('is_free');
    const requireShareSection = document.getElementById('require_share_section');
    const packagePriceInputs = document.querySelectorAll(
        'input[name="packages[basic][price]"], input[name="packages[standard][price]"], input[name="packages[premium][price]"]'
    );

    function applyFreeServiceState(isFree) {
        requireShareSection.classList.toggle('d-none', !isFree);
        packagePriceInputs.forEach(input => {
            if (isFree) {
                input.value = '0';
                input.min = '0';
                input.setAttribute('readonly', true);
            } else {
                input.removeAttribute('readonly');
                input.min = '0';
                if (input.value === '0') input.value = '';
            }
        });
    }

    // Auto-enable free mode if user types 0
    packagePriceInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (parseFloat(this.value) === 0 && isFreeCheckbox && !isFreeCheckbox.checked) {
                isFreeCheckbox.checked = true;
                applyFreeServiceState(true);
            }
        });
    });

    if (isFreeCheckbox) {
        isFreeCheckbox.addEventListener('change', function() {
            applyFreeServiceState(this.checked);
        });
        // Apply on page load in case of old() repopulation
        applyFreeServiceState(isFreeCheckbox.checked);
    }

    // Autosave Draft
    function saveDraft() {
        const formData = new FormData(form);
        const draft = {};
        for (let [key, value] of formData.entries()) {
            if (!(value instanceof File)) draft[key] = value;
        }
        localStorage.setItem('service_draft', JSON.stringify(draft));
        
        autosaveBadge.classList.remove('d-none');
        setTimeout(() => autosaveBadge.classList.add('d-none'), 2000);
    }

    // Load Draft
    const savedDraft = localStorage.getItem('service_draft');
    if (savedDraft) {
        const draft = JSON.parse(savedDraft);
        Object.keys(draft).forEach(key => {
            const input = form.elements[key];
            if (input && input.type !== 'file') {
                input.value = draft[key];
            }
        });
        updatePreview();
    }

    // AI Generation
    document.getElementById('btnGenerateAI').addEventListener('click', function() {
        const prompt = document.getElementById('aiPrompt').value;
        const btn = this;
        const spinner = btn.querySelector('.spinner-border');
        const errorDiv = document.getElementById('aiError');

        if (!prompt || prompt.trim().length < 5) {
            errorDiv.textContent = "{{ __('services.ai.prompt_too_short') }}";
            errorDiv.classList.remove('d-none');
            return;
        }

        errorDiv.classList.add('d-none');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        fetch("{{ route('services.generate-ai') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ prompt: prompt })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            
            if (data.title_en) titleEn.value = data.title_en;
            if (data.title_ar) document.getElementById('title_ar').value = data.title_ar;
            if (data.tagline_en) document.getElementById('tagline_en').value = data.tagline_en;
            if (data.tagline_ar) document.getElementById('tagline_ar').value = data.tagline_ar;
            if (data.description_en) document.getElementById('description_en').value = data.description_en;
            if (data.description_ar) document.getElementById('description_ar').value = data.description_ar;
            if (data.price_suggestion) priceInput.value = data.price_suggestion;

            bootstrap.Modal.getInstance(document.getElementById('aiAutoFillModal')).hide();
            updatePreview();
        })
        .catch(error => {
            errorDiv.textContent = error.message;
            errorDiv.classList.remove('d-none');
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
        });
    });
});
</script>
@endpush
