@if($landingPage->questions->count() > 0)
    <div class="form-section" id="contact-form">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="form-card">
                        <h2 class="text-center mb-4">{{ $landingPage->form_config['form_title'] ?? 'Get in Touch' }}</h2>

                        <form action="{{ route('services.landing-page.submit-form-public', $landingPage->slug) }}" method="POST">
                            @csrf

                            <!-- UTM Tracking Fields -->
                            <input type="hidden" name="utm_source" value="{{ request('utm_source') }}">
                            <input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">
                            <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">
                            <input type="hidden" name="utm_term" value="{{ request('utm_term') }}">
                            <input type="hidden" name="utm_content" value="{{ request('utm_content') }}">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone (Optional)</label>
                                <input type="tel" id="phone" name="phone" class="form-control">
                            </div>

                            @foreach($landingPage->questions as $question)
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ $question->question_text }}
                                        @if($question->is_required)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>

                                    @if($question->field_type == 'textarea')
                                        <textarea name="question_{{ $question->id }}"
                                                  class="form-control"
                                                  rows="4"
                                                  placeholder="{{ $question->placeholder }}"
                                                  {{ $question->is_required ? 'required' : '' }}></textarea>
                                    @elseif(in_array($question->field_type, ['select', 'radio', 'checkbox']))
                                        @php
                                            $options = $question->field_options ?? [];
                                        @endphp
                                        @if($question->field_type == 'select')
                                            <select name="question_{{ $question->id }}"
                                                    class="form-control"
                                                    {{ $question->is_required ? 'required' : '' }}>
                                                <option value="">-- Select --</option>
                                                @foreach($options as $option)
                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($question->field_type == 'radio')
                                            @foreach($options as $option)
                                                <div class="form-check">
                                                    <input type="radio"
                                                           name="question_{{ $question->id }}"
                                                           value="{{ $option }}"
                                                           class="form-check-input"
                                                           {{ $question->is_required ? 'required' : '' }}>
                                                    <label class="form-check-label">{{ $option }}</label>
                                                </div>
                                            @endforeach
                                        @elseif($question->field_type == 'checkbox')
                                            @foreach($options as $option)
                                                <div class="form-check">
                                                    <input type="checkbox"
                                                           name="question_{{ $question->id }}[]"
                                                           value="{{ $option }}"
                                                           class="form-check-input">
                                                    <label class="form-check-label">{{ $option }}</label>
                                                </div>
                                            @endforeach
                                        @endif
                                    @else
                                        <input type="{{ $question->field_type }}"
                                               name="question_{{ $question->id }}"
                                               class="form-control"
                                               placeholder="{{ $question->placeholder }}"
                                               {{ $question->is_required ? 'required' : '' }}>
                                    @endif

                                    @if($question->help_text)
                                        <small class="text-muted">{{ $question->help_text }}</small>
                                    @endif
                                </div>
                            @endforeach

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5" aria-label="Paper Plane">
                                    <i class="fas fa-paper-plane me-2"></i>{{ $landingPage->form_config['submit_button_text'] ?? 'Submit' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
    function toggleFaq(element) {
        const answer = element.nextElementSibling;
        const icon = element.querySelector('i');

        // Close all other FAQs
        document.querySelectorAll('.faq-answer').forEach(faq => {
            if (faq !== answer) {
                faq.classList.remove('active');
            }
        });
        document.querySelectorAll('.faq-question i').forEach(ic => {
            if (ic !== icon) {
                ic.className = 'fas fa-chevron-down';
            }
        });

        // Toggle current FAQ
        answer.classList.toggle('active');
        icon.className = answer.classList.contains('active')
            ? 'fas fa-chevron-up'
            : 'fas fa-chevron-down';
    }

    // Smooth scroll to form
    document.querySelectorAll('a[href="#contact-form"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('contact-form');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
</script>
