@extends('layouts.app')

@section('title', __('services.edit.page_title'))



@section('content')
    <div class="dashboard-container at-mobile-scroll-fix">
        <x-client.section-header 
            icon="ti ti-edit" 
            title="{{ __('services.edit.title') }}" 
            subtitle="{{ __('services.edit.subtitle') }}">
            <a href="{{ route('services.mine') }}" class="at-btn at-btn-ghost at-btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('services.back_to_services') }}
            </a>
        </x-client.section-header>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <form class="sidebar-login" enctype="multipart/form-data" action="{{ route('services.update', $service) }}"
                    method="POST" id="serviceForm">
                    <input type="hidden" name="removed_images" id="removedImages" value="">
                    @csrf
                    @method('put')

                    <x-client.form-card icon="info-circle" iconClass="text-primary" title="{{ __('services.form.service_details') }}">

                            <!-- AI Auto Fill Button -->
                            <div class="d-flex justify-content-end mb-3">
                                <button type="button" class="at-btn at-btn-primary at-btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#aiAutoFillModal">
                                    <i class="ti ti-wand me-2"></i>{{ __('services.ai.auto_fill_with_ai') }}
                                </button>
                            </div>

                            <!-- AI Modal -->
                            <div class="modal fade" id="aiAutoFillModal" tabindex="-1" aria-hidden="true"
                                style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('services.ai.auto_fill_with_ai') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="aiPrompt"
                                                    class="form-label">{{ __('services.ai.describe_your_service') }}</label>
                                                <textarea class="form-control" id="aiPrompt" rows="3"
                                                    placeholder="{{ __('services.ai.prompt_placeholder') }}"></textarea>
                                                <div class="form-text">
                                                    {{ __('services.ai.edit_help_text') }}
                                                </div>
                                            </div>
                                            <div id="aiError" class="alert alert-danger d-none"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="at-btn at-btn-ghost" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                                            <button type="button" class="at-btn at-btn-primary" id="btnGenerateAI" aria-label="Action">
                                                <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                                                {{ __('common.generate') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert-banner mb-4">
                                <div class="alert-content">
                                    <div class="alert-icon text-info">
                                        <i class="ti ti-bulb"></i>
                                    </div>
                                    <div class="alert-text">
                                        <div class="alert-title">{{ __('services.tip.title') }}</div>
                                        <div class="alert-description">
                                            {{ __('services.tip.edit_help_text') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <ul class="nav nav-tabs mb-4 border-bottom" id="languageTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active px-4 py-2 fw-semibold text-dark" id="english-tab"
                                        data-bs-toggle="tab" data-bs-target="#english" type="button" role="tab">
                                        <span class="d-flex align-items-center">
                                            <span class="me-2">🇬🇧</span> {{ __('common.english') }}
                                        </span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link px-4 py-2 fw-semibold text-dark" id="arabic-tab"
                                        data-bs-toggle="tab" data-bs-target="#arabic" type="button" role="tab">
                                        <span class="d-flex align-items-center">
                                            <span class="me-2">🇸🇦</span> {{ __('common.arabic') }}
                                        </span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="languageTabContent">
                                {{-- English Tab --}}
                                <div class="tab-pane fade show active" id="english" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="title_en" class="form-label fw-bold">{{ __('services.form.title_english') }}</label>
                                        <input type="text" id="title_en" name="title_en" class="form-control"
                                            value="{{ old('title_en', $service->getTranslation('title', 'en') ?? ($service->getRawOriginal('title') ?? '')) }}"
                                            placeholder="{{ __('services.placeholders.title_en') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="tagline_en" class="form-label fw-bold">{{ __('services.form.tagline_english') }}</label>
                                        <input type="text" id="tagline_en" name="tagline_en" class="form-control"
                                            value="{{ old('tagline_en', $service->getTranslation('tagline', 'en') ?? ($service->getRawOriginal('tagline') ?? '')) }}"
                                            placeholder="{{ __('services.placeholders.tagline_en') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label for="description_en" class="form-label fw-bold">{{ __('services.form.description_english') }}</label>
                                        <textarea name="description_en" id="description_en" class="form-control" rows="5"
                                            placeholder="{{ __('services.placeholders.description_en') }}">{{ old('description_en', $service->getTranslation('description', 'en') ?? ($service->getRawOriginal('description') ?? '')) }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="auto_reply_en" class="form-label fw-bold">{{ __('services.form.auto_reply_message_optional') }}</label>
                                        <textarea name="auto_reply_en" id="auto_reply_en" class="form-control" rows="3"
                                            placeholder="{{ __('services.placeholders.auto_reply_en') }}">{{ old('auto_reply_en', $service->getTranslation('auto_reply', 'en') ?? ($service->getRawOriginal('auto_reply') ?? '')) }}</textarea>
                                        <div class="form-text">{{ __('services.form.auto_reply_help_text') }}</div>
                                    </div>
                                </div>

                                {{-- Arabic Tab --}}
                                <div class="tab-pane fade" id="arabic" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="title_ar" class="form-label fw-bold">العنوان (عربي)</label>
                                        <input type="text" id="title_ar" name="title_ar" class="form-control" dir="rtl"
                                            value="{{ old('title_ar', $service->getTranslation('title', 'ar') ?? '') }}"
                                            placeholder="مثال: تصميم شعار احترافي">
                                    </div>

                                    <div class="mb-3">
                                        <label for="tagline_ar" class="form-label fw-bold">الشعار (عربي)</label>
                                        <input type="text" id="tagline_ar" name="tagline_ar" class="form-control" dir="rtl"
                                            value="{{ old('tagline_ar', $service->getTranslation('tagline', 'ar') ?? '') }}"
                                            placeholder="مثال: تميز بهوية فريدة">
                                    </div>

                                    <div class="mb-3">
                                        <label for="description_ar" class="form-label fw-bold">الوصف (عربي)</label>
                                        <textarea name="description_ar" id="description_ar" class="form-control" rows="5"
                                            dir="rtl"
                                            placeholder="اكتب تفاصيل خدمتك بالتفصيل...">{{ old('description_ar', $service->getTranslation('description', 'ar') ?? '') }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="auto_reply_ar" class="form-label fw-bold">رسالة الرد التلقائي
                                            (اختياري)</label>
                                        <textarea name="auto_reply_ar" id="auto_reply_ar" class="form-control" rows="3"
                                            dir="rtl"
                                            placeholder="يتم إرسالها تلقائيًا عند طلب الخدمة...">{{ old('auto_reply_ar', $service->getTranslation('auto_reply', 'ar') ?? '') }}</textarea>
                                        <div class="form-text">سيتم إرسال هذه الرسالة تلقائيًا للمشتري عند الطلب.</div>
                                    </div>
                                </div>
                            </div>
                    </x-client.form-card>

                    <x-client.form-card icon="settings" iconClass="text-secondary" title="{{ __('services.form.pricing_categorization') }}">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="price" class="form-label fw-bold">{{ __('common.price') }} <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" id="price" name="price" step="0.01" min="0"
                                            class="form-control" required value="{{ old('price', $service->price) }}">
                                        <select name="currency" id="currency" class="form-select" style="max-width: 100px;">
                                            <option value="" disabled selected>{{ __('common.select') }}</option>
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency->id }}" {{ old('currency', $service->currency) == $currency->id ? 'selected' : '' }}>
                                                    {{ $currency->currency }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="service_category_id" class="form-label fw-bold">{{ __('common.category') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="service_category_id" id="service_category_id" class="form-select"
                                        required>
                                        <option value="" disabled selected>{{ __('services.form.select_category') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('service_category_id', $service->service_category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                            placeholder="{{ __('services.placeholders.original_service_link') }}">
                                    </div>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="generate_serials"
                                            name="generate_serials" value="1" {{ old('generate_serials', $service->generate_serials) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="generate_serials">
                                            <strong>{{ __('services.form.generate_serials') }}</strong>
                                            <div class="form-text text-muted mt-0">
                                                {{ __('services.form.generate_serials_help') }}
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mt-2 ms-4" id="random_serial_container"
                                        style="display: {{ $service->generate_serials ? 'block' : 'none' }};">
                                        <div class="mb-2">
                                            <input class="form-check-input" type="checkbox" id="allow_random_serial"
                                                name="allow_random_serial" value="1" {{ old('allow_random_serial', $service->allow_random_serial) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_random_serial">
                                                <strong>{{ __('services.form.allow_random_serials') }}</strong>
                                                <div class="form-text text-muted mt-0">
                                                    {{ __('services.form.allow_random_serials_help') }}
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mt-3 ms-4" id="validity_period_container"
                                        style="display: {{ $service->generate_serials ? 'block' : 'none' }};">
                                        <label for="validity_days" class="form-label mb-1">
                                            <strong>{{ __('services.form.validity_period_days') }}</strong>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i
                                                    class="ti ti-calendar-time"></i></span>
                                            <input type="number" class="form-control border-start-0 ps-0" id="validity_days"
                                                name="validity_days"
                                                value="{{ old('validity_days', $service->validity_days) }}" min="1"
                                                placeholder="{{ __('services.placeholders.validity_days') }}">
                                        </div>
                                        <div class="form-text text-muted">{{ __('services.form.validity_days_help') }}</div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top">
                                        <label class="form-label fw-bold">{{ __('services.edit.referral_commission') }}</label>
                                        <div class="form-text text-muted mb-2">{{ __('services.edit.referral_commission_hint') }}</div>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="referral_commission_from" id="ref_from_fee" value="fee"
                                                    {{ old('referral_commission_from', $service->referral_commission_from ?? 'fee') === 'fee' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ref_from_fee">{{ __('services.edit.ref_from_fee') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="referral_commission_from" id="ref_from_seller" value="seller_price"
                                                    {{ old('referral_commission_from', $service->referral_commission_from) === 'seller_price' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ref_from_seller">{{ __('services.edit.ref_from_seller') }}</label>
                                            </div>
                                        </div>
                                        <div class="form-text text-muted mt-1">{{ __('services.edit.ref_from_seller_hint') }}</div>
                                        <div class="mt-3">
                                            <label for="referral_commission_percentage" class="form-label">{{ __('services.edit.referral_percentage_label') }}</label>
                                            <div class="input-group" style="max-width: 180px;">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="referral_commission_percentage"
                                                    name="referral_commission_percentage" value="{{ old('referral_commission_percentage', $service->referral_commission_percentage) }}"
                                                    placeholder="10">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text text-muted mt-1">{{ __('services.edit.referral_percentage_hint') }}</div>
                                        </div>
                                    </div>

                                    @if (!empty($service->service_link))
                                        <div class="mt-2">
                                            <a href="{{ $service->service_link }}" target="_blank" rel="noopener noreferrer"
                                                class="small text-decoration-none">
                                                <i class="ti ti-external-link me-1"></i>{{ __('general.open_current_link') }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                    </x-client.form-card>

                    <!-- Service Extras (Upsells) -->
                    <x-client.form-card icon="plus" iconClass="text-success" title="{{ __('services.service_extras_upsells') }}">
                            <div id="extras-container">
                                @foreach($service->extras as $index => $extra)
                                    <div class="row g-3 mb-3 pb-3 border-bottom extra-row">
                                        <div class="col-md-4">
                                            <input type="text" name="extras[{{ $index }}][title]" class="form-control"
                                                placeholder="{{ __('general.title_e_g_fast_delivery') }}" value="{{ $extra->title }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text extra-currency-symbol">
                                                    @php
                                                        $selectedCurrency = $currencies->find(old('currency', $service->currency));
                                                        echo $selectedCurrency ? $selectedCurrency->currency : '$';
                                                    @endphp
                                                </span>
                                                <input type="number" name="extras[{{ $index }}][price]" class="form-control"
                                                    placeholder="Price" step="0.01" min="1" value="{{ $extra->price }}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" name="extras[{{ $index }}][duration_days]" class="form-control"
                                                placeholder="{{ __('general.extra_days') }}" min="0" value="{{ $extra->duration_days }}">
                                            <div class="form-text mt-0" style="font-size: 0.75rem;">{{ __('general.duration_added_to_delivery') }}</div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                                onclick="this.closest('.extra-row').remove()">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-success btn-sm mt-2" id="btn-add-extra">
                                <i class="ti ti-plus me-1"></i>{{ __('general.add_service_extra') }}</button>
                    </x-client.form-card>

                    <!-- Service FAQs -->
                    <x-client.form-card icon="help" iconClass="text-info" title="{{ __('services.form.faqs_title') }}">
                            <div id="faqs-container">
                                @foreach($service->faqs as $index => $faq)
                                    <div class="row g-3 mb-3 pb-3 border-bottom faq-row">
                                        <div class="col-md-5">
                                            <input type="text" name="faqs[{{ $index }}][question]" class="form-control"
                                                placeholder="Question" value="{{ $faq->question }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <textarea name="faqs[{{ $index }}][answer]" class="form-control" rows="1"
                                                placeholder="Answer" required>{{ $faq->answer }}</textarea>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                                onclick="this.closest('.faq-row').remove()">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-info btn-sm mt-2" id="btn-add-faq">
                                <i class="ti ti-plus me-1"></i>{{ __('general.add_faq') }}</button>
                    </x-client.form-card>

                    <x-client.form-card icon="photo" iconClass="text-warning" title="{{ __('services.form.gallery_title') }}" class="mb-5">
                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('general.service_gallery') }}<span class="badge bg-secondary ms-2">
                                        <span id="imageCount">{{ $service->images->count() }}</span>/5
                                    </span>
                                </label>
                                <div class="form-text">{{ __('general.upload_up_to_5_images_max_size_2mb_each_jpg_png_webp_supported') }}</div>
                            </div>

                            <!-- Current Images Gallery -->
                            <div class="mb-4">
                                <div id="imagesGallery" class="sortable-images">
                                    @foreach($service->images->sortBy('sort_order') as $img)
                                        <div class="image-gallery-item" data-id="{{ $img->id }}" data-sort="{{ $img->sort_order }}">
                                            <div class="image-container {{ $img->is_main ? 'main-image' : '' }}">
                                                <img src="{{ asset($img->image_path) }}" alt="Service Image" class="gallery-image">
                                                
                                                @if($img->is_main)
                                                    <div class="main-badge">
                                                        <i class="ti ti-star-filled"></i> Main
                                                    </div>
                                                @endif
                                                
                                                <div class="image-actions">
                                                    @if(!$img->is_main)
                                                        <button type="button" class="btn btn-sm btn-primary set-main-btn" 
                                                                data-id="{{ $img->id }}" title="{{ __('general.set_as_main') }}">
                                                            <i class="ti ti-star"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-danger delete-image-btn" 
                                                            data-id="{{ $img->id }}" title="Delete">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">{{ __('general.additional_images') }}</label>
                                @if($service->images->count() > 0)
                                    <div class="row g-2 mb-3" id="existingImages">
                                        @foreach($service->images as $img)
                                            <div class="col-4 col-sm-3 image-item" data-id="{{ $img->id }}">
                                                <div class="position-relative">
                                                    <img alt="Image" src="{{ asset($img->image_path) }}"
                                                        class="img-fluid rounded border shadow-sm">
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-0 rounded-circle d-flex align-items-center justify-content-center remove-image"
                                                        style="width: 24px; height: 24px;" data-id="{{ $img->id }}">
                                                        <i class="ti ti-x" style="font-size: 14px;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <input type="file" id="images" name="images[]" class="form-control" accept="image/*"
                                    multiple>
                                <div class="form-text">{{ __('general.selection_allows_multiple_images_max_1mb_each') }}</div>
                            </div>
                    </x-client.form-card>

                    <div class="d-flex justify-content-end gap-2 mb-5">
                        <a href="{{ route('services.mine') }}" class="btn btn-outline-secondary px-4">{{ __('common.cancel') }}</a>
                        <button type="submit" name="status" value="draft" class="btn btn-secondary px-4">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save as Draft') }}
                        </button>
                        <button type="submit" name="status" value="approved" class="at-btn at-btn-primary" aria-label="Action">
                            <i class="ti ti-circle-check me-1"></i>{{ __('services.edit.save_changes') }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
/* Image Gallery Styles */
.sortable-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.image-gallery-item {
    position: relative;
}

.image-container {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.image-container:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.gallery-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.main-image {
    border-color: #28a745;
}

.main-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 4px;
}

.preview-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.image-actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-container:hover .image-actions {
    opacity: 1;
}

.image-actions .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

/* Upload Area Styles */
.image-upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.image-upload-area:hover {
    border-color: #007bff;
    background: #e3f2fd;
}

.image-upload-area.drag-over {
    border-color: #007bff;
    background: #e3f2fd;
}

.image-upload-area.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #e9ecef;
}

.upload-icon {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.upload-text h6 {
    margin-bottom: 0.5rem;
    color: #495057;
}

.upload-text p {
    margin-bottom: 0;
    font-size: 0.875rem;
}

/* Sortable Ghost Class */
.sortable-ghost {
    opacity: 0.5;
}

.sortable-ghost .image-container {
    border-color: #007bff;
    transform: scale(0.95);
}

/* Preview Item Styles */
.preview-item {
    border: 2px solid #007bff;
}

.preview-item .image-container {
    border-color: #007bff;
}

/* Progress Bar */
.progress {
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    background-color: #007bff;
    transition: width 0.3s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sortable-images {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 0.5rem;
    }
    
    .gallery-image {
        height: 100px;
    }
    
    .image-actions .btn {
        width: 28px;
        height: 28px;
    }
    
    .main-badge, .preview-badge {
        font-size: 10px;
        padding: 2px 6px;
    }
}
</style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle Random Serial Checkbox
            const generateSerialsCheckbox = document.getElementById('generate_serials');
            const randomSerialContainer = document.getElementById('random_serial_container');
            const validityPeriodContainer = document.getElementById('validity_period_container');

            if (generateSerialsCheckbox && randomSerialContainer) {
                function toggleRandomSerial() {
                    if (generateSerialsCheckbox.checked) {
                        randomSerialContainer.style.display = 'block';
                        if (validityPeriodContainer) validityPeriodContainer.style.display = 'block';
                    } else {
                        randomSerialContainer.style.display = 'none';
                        if (validityPeriodContainer) validityPeriodContainer.style.display = 'none';
                        document.getElementById('allow_random_serial').checked = false;
                        if (document.getElementById('validity_days')) document.getElementById('validity_days').value = '';
                    }
                }

                // Initial check is handled by blade directive, but we add listener for changes
                generateSerialsCheckbox.addEventListener('change', toggleRandomSerial);
            }

            // Free Service Toggle
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

            // Enhanced Image Management
            const serviceId = {{ $service->id }};
            let selectedFiles = [];
            let currentImageCount = {{ $service->images->count() }};
            const maxImages = 5;

            // Initialize sortable for images gallery
            initializeSortable();

            function initializeSortable() {
                const gallery = document.getElementById('imagesGallery');
                if (gallery && typeof Sortable !== 'undefined') {
                    Sortable.create(gallery, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function(evt) {
                            updateImageOrder();
                        }
                    });
                }
            }

            function updateImageOrder() {
                const items = document.querySelectorAll('#imagesGallery .image-gallery-item');
                const orders = [];
                items.forEach(item => {
                    orders.push(item.dataset.id);
                });

                fetch(`/services/${serviceId}/images/reorder`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ orders: orders })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to reorder images:', data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Upload area click handler
            const uploadArea = document.getElementById('imageUploadArea');
            const imageInput = document.getElementById('imageInput');

            if (uploadArea && imageInput) {
                uploadArea.addEventListener('click', function() {
                    if (currentImageCount < maxImages) {
                        imageInput.click();
                    }
                });

                // Drag and drop
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('drag-over');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                    handleFiles(e.dataTransfer.files);
                });
            }

            // File input change handler
            imageInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                const remainingSlots = maxImages - currentImageCount;
                const filesToProcess = Array.from(files).slice(0, remainingSlots);
                
                if (files.length > remainingSlots) {
                    alert(`Only ${remainingSlots} more images can be uploaded (max ${maxImages} total)`);
                }

                selectedFiles = filesToProcess;
                showPreviews();
            }

            function showPreviews() {
                const previewArea = document.getElementById('previewArea');
                const previewImages = document.getElementById('previewImages');
                
                if (selectedFiles.length === 0) {
                    previewArea.classList.add('d-none');
                    return;
                }

                previewImages.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'image-gallery-item preview-item';
                        previewItem.innerHTML = `
                            <div class="image-container">
                                <img src="${e.target.result}" alt="Preview" class="gallery-image">
                                <div class="preview-badge">New</div>
                                <div class="image-actions">
                                    <button type="button" class="btn btn-sm btn-danger remove-preview-btn" data-index="${index}">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        previewImages.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                });

                previewArea.classList.remove('d-none');
                initializeSortable();
            }

            // Upload images
            document.getElementById('uploadImagesBtn')?.addEventListener('click', uploadImages);
            document.getElementById('cancelUploadBtn')?.addEventListener('click', cancelUpload);

            function uploadImages() {
                if (selectedFiles.length === 0) return;

                const progressContainer = document.getElementById('uploadProgress');
                const progressBar = progressContainer.querySelector('.progress-bar');
                const statusText = progressContainer.querySelector('.upload-status');
                
                progressContainer.classList.remove('d-none');
                
                const formData = new FormData();
                selectedFiles.forEach(file => {
                    formData.append('images[]', file);
                });

                fetch(`/services/${serviceId}/images`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh to show new images
                    } else {
                        alert('Upload failed: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    alert('Upload failed. Please try again.');
                })
                .finally(() => {
                    progressContainer.classList.add('d-none');
                });
            }

            function cancelUpload() {
                selectedFiles = [];
                document.getElementById('previewArea').classList.add('d-none');
                imageInput.value = '';
            }

            // Set main image
            document.querySelectorAll('.set-main-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const imageId = this.dataset.id;
                    
                    fetch(`/services/${serviceId}/images/${imageId}/main`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload(); // Refresh to update main image badge
                        } else {
                            alert('Failed to set main image: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to set main image. Please try again.');
                    });
                });
            });

            // Delete image
            document.querySelectorAll('.delete-image-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const imageId = this.dataset.id;
                    
                    if (confirm('Are you sure you want to delete this image?')) {
                        fetch(`/services/${serviceId}/images/${imageId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload(); // Refresh to update gallery
                            } else {
                                alert('Failed to delete image: ' + data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to delete image. Please try again.');
                        });
                    }
                });
            });

            // Remove preview
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-preview-btn')) {
                    const button = e.target.closest('.remove-preview-btn');
                    const index = parseInt(button.dataset.index);
                    selectedFiles.splice(index, 1);
                    showPreviews();
                }
            });

            // Update image count display
            function updateImageCount() {
                document.getElementById('imageCount').textContent = currentImageCount;
                const uploadArea = document.getElementById('imageUploadArea');
                if (currentImageCount >= maxImages) {
                    uploadArea.classList.add('disabled');
                } else {
                    uploadArea.classList.remove('disabled');
                }
            }

            updateImageCount();

            // AI Generation Logic
            const btnGenerateAI = document.getElementById('btnGenerateAI');
            if (btnGenerateAI) {
                btnGenerateAI.addEventListener('click', function () {
                    const prompt = document.getElementById('aiPrompt').value;
                    const btn = this;
                    const spinner = btn.querySelector('.spinner-border');
                    const errorDiv = document.getElementById('aiError');

                    if (!prompt || prompt.trim().length < 5) {
                        errorDiv.textContent = 'Please provide a longer description.';
                        errorDiv.classList.remove('d-none');
                        return;
                    }

                    // Reset state
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
                            if (data.error) {
                                throw new Error(data.error);
                            }

                            // Populate fields
                            if (data.title_en) document.getElementById('title_en').value = data.title_en;
                            if (data.tagline_en) document.getElementById('tagline_en').value = data.tagline_en;
                            if (data.description_en) document.getElementById('description_en').value = data.description_en;
                            if (data.auto_reply_en) document.getElementById('auto_reply_en').value = data.auto_reply_en;

                            if (data.title_ar) document.getElementById('title_ar').value = data.title_ar;
                            if (data.tagline_ar) document.getElementById('tagline_ar').value = data.tagline_ar;
                            if (data.description_ar) document.getElementById('description_ar').value = data.description_ar;
                            if (data.auto_reply_ar) document.getElementById('auto_reply_ar').value = data.auto_reply_ar;

                            if (data.price_suggestion) {
                                document.getElementById('price').value = data.price_suggestion;
                            }

                            // Close modal
                            const modalElement = document.getElementById('aiAutoFillModal');
                            const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                            modal.hide();
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
            }
            // Dynamic FAQs
            const faqsContainer = document.getElementById('faqs-container');
            let faqCount = {{ $service->faqs->count() }}; // Start from existing count

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
            let extraCount = {{ $service->extras->count() }}; // Start from existing count

            function getCurrencySymbol() {
                const currencySelect = document.getElementById('currency');
                if (currencySelect && currencySelect.selectedIndex >= 0) {
                    return currencySelect.options[currencySelect.selectedIndex].text.trim();
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
    </script>
@endpush