<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('sms_gateway.checkout_title') }} - {{ $merchantName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'sans-serif'] },
                    colors: { primary: '#4B43D6', 'primary-hover': '#3A32A8' }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3); 
            border-top-color: #fff; 
            border-radius: 50%; 
            width: 20px; 
            height: 20px; 
            animation: spin 1s linear infinite; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .method-card.selected, input:checked + .method-card {
            border-color: #4B43D6 !important;
            box-shadow: 0 0 0 3px rgba(75, 67, 214, 0.15) !important;
            background-color: #F6F6FF !important;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<div class="bg-white rounded-[22px] overflow-hidden shadow-[0_8px_40px_rgba(75,67,214,0.13)] w-full max-w-md fade-in border border-gray-100 relative">
    
    @if($state === 'success')
    <!-- SUCCESS STATE -->
    <div class="text-center py-12 px-6">
        <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-green-100">
            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-3xl font-black text-[#1A1A3E] mb-2">{{ __('sms_gateway.payment_successful') }}</h3>
        <p class="text-gray-500 font-semibold mb-8 text-[14px]">{{ __('sms_gateway.payment_confirmed_message') }}</p>
        
        @if($session->success_url)
            <a href="{{ $session->getSuccessRedirectUrl() }}" target="_top" class="inline-block w-full bg-primary text-white font-extrabold py-4 rounded-xl hover:bg-primary-hover transition-all">{{ __('sms_gateway.return_to_merchant') }}</a>
        @endif
    </div>

    @elseif($state === 'expired')
    <!-- EXPIRED STATE -->
    <div class="text-center py-12 px-6">
        <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-red-100">
            <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="text-2xl font-black text-[#1A1A3E] mb-2">{{ __('sms_gateway.session_expired_title') }}</h3>
        <p class="text-gray-500 font-semibold mb-8 text-[14px]">{{ __('sms_gateway.session_expired_message') }}</p>
        
        @if(isset($cancelUrl) && $cancelUrl)
            <a href="{{ $cancelUrl }}" target="_top" class="inline-block w-full bg-[#111220] text-white font-extrabold py-4 rounded-xl hover:opacity-90 transition-all">{{ __('sms_gateway.return_to_merchant') }}</a>
        @endif
    </div>

    @else
    <!-- OPEN STATE (Payment Form) -->
    <div id="payment-container">
        <!-- Header -->
        <div class="bg-primary pt-[20px] px-[20px] pb-[24px] text-center text-white relative">
            <div class="text-[17px] font-bold tracking-[0.3px] mb-1 text-white">{{ $merchantName }}</div>
            <div class="text-[14px] font-medium text-white/80 mb-5">{{ __('sms_gateway.requesting_payment') }}</div>

            <div class="flex flex-col items-center gap-[14px]">
                <div class="flex items-center justify-center gap-2" dir="ltr">
                    <div class="text-[20px] font-extrabold text-white mt-1">{{ $currency }}</div>
                    <div class="text-[48px] font-black leading-none text-white tracking-[-1px]">{{ number_format($amount, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="px-4 py-4 flex flex-col gap-4 bg-white">
            
            <!-- Step 1 -->
            <div class="bg-[#EEF2FF] border border-[#D8DFFA] rounded-[16px] px-3 py-3.5">
                <div class="flex items-center justify-start gap-2 mb-2">
                    <div class="w-[26px] h-[26px] bg-primary text-white rounded-full flex items-center justify-center text-[13px] font-extrabold shrink-0">1</div>
                    <span class="text-[14px] font-extrabold text-[#1A1A3E]">{{ __('sms_gateway.choose_method_and_transfer') }}</span>
                </div>
                <p class="text-[11.5px] text-[#555570] mb-3 text-start leading-relaxed font-medium tracking-tight">{{ __('sms_gateway.transfer_instructions', ['amount' => number_format($amount, 2)]) }}</p>
                
                <div class="bg-white border border-[#DDE2F5] rounded-xl px-3 py-2.5 flex items-center justify-between">
                    <span class="text-[18px] font-extrabold text-[#1A1A3E] tracking-wider" dir="ltr" id="wallet-display">{{ $paymentMethods[0]['phone'] ?? '' }}</span>
                    <button type="button" onclick="copyWallet()" id="copy-btn" class="bg-[#E8ECFF] hover:bg-[#D4DAFF] active:bg-[#C2CCFF] active:scale-95 text-primary-hover font-bold text-[12px] py-[6px] px-[14px] rounded-[8px] transition-all">{{ __('sms_gateway.copy') }}</button>
                </div>
            </div>

            <form id="verify-form" onsubmit="submitPayment(event)" class="flex flex-col gap-4">
                
                <!-- Step 2 -->
                <div class="flex flex-col">
                    <div class="flex items-center justify-start gap-2 mb-2.5">
                        <div class="w-[26px] h-[26px] bg-primary text-white rounded-full flex items-center justify-center text-[13px] font-extrabold shrink-0">2</div>
                        <span class="text-[14px] font-extrabold text-[#1A1A3E]">{{ app()->getLocale() == 'ar' ? 'اختر طريقة التحويل التي استخدمتها:' : 'Select your payment method:' }}</span>
                    </div>

                    <div class="grid grid-cols-[repeat(auto-fit,minmax(120px,1fr))] gap-3">
                        @foreach($paymentMethods as $i => $method)
                        <label class="cursor-pointer group">
                            <input type="radio" name="method" value="{{ $method['id'] }}" data-phone="{{ $method['phone'] }}" data-is-etisalat="{{ isset($method['is_etisalat']) && $method['is_etisalat'] ? 'true' : 'false' }}" class="peer sr-only" {{ $i === 0 ? 'checked' : '' }} required>
                            <div class="method-card bg-white border-[1.5px] border-[#E4E8F5] rounded-[14px] p-2 flex flex-col items-center justify-center min-h-[70px] transition-all group-hover:border-[#9898D8]">
                                <div class="flex items-center justify-center gap-1.5 flex-nowrap mb-1 w-full overflow-hidden">
                                    <img src="{{ $method['icon'] }}" alt="{{ $method['name'] }}" class="h-[28px] shrink object-contain">
                                    @if(isset($method['additional_icons']))
                                        @foreach($method['additional_icons'] as $icon)
                                            <img src="{{ $icon }}" alt="wallet" class="h-[28px] shrink object-contain">
                                        @endforeach
                                    @endif
                                </div>
                                @if(!($hideMethodName ?? false))
                                <span class="text-[12px] font-bold text-[#444460] text-center">{{ $method['name'] }}</span>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col">
                    <div class="flex items-center justify-start gap-2 mb-2.5">
                        <div class="w-[26px] h-[26px] bg-primary text-white rounded-full flex items-center justify-center text-[13px] font-extrabold shrink-0">3</div>
                        <span class="text-[14px] font-extrabold text-[#1A1A3E]" id="step2-label-text">{{ __('sms_gateway.enter_transaction_reference') }}</span>
                    </div>
                    <input type="text" id="ref-input" required 
                           placeholder="{{ __('sms_gateway.reference_placeholder') }}"
                           class="w-full py-[14px] px-[16px] border-[1.5px] border-[#E4E8F5] rounded-[13px] text-[14px] bg-white {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }} font-medium text-[#1A1A2E] outline-none transition-all focus:border-primary focus:shadow-[0_0_0_3px_rgba(75,67,214,0.1)] placeholder:text-[#A8A8BC] placeholder:text-[13.5px] placeholder:font-medium" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
                </div>

                <!-- ERROR MSG -->
                <div id="error-alert" class="hidden text-[13px] text-red-600 p-3 bg-red-50 rounded-xl border border-red-100 text-center font-bold"></div>

                <!-- SUBMIT -->
                <button type="submit" id="submit-btn" class="w-full py-[14px] bg-[#111220] hover:opacity-90 active:scale-95 text-white border-none rounded-[14px] text-[16px] font-extrabold tracking-[0.3px] cursor-pointer transition-all flex justify-center items-center shadow-md">
                    <span id="btn-text">{{ __('sms_gateway.confirm_payment') }}</span>
                    <div id="btn-spinner" class="spinner hidden mr-3 {{ app()->getLocale() == 'ar' ? 'mr-3' : 'ml-3' }}"></div>
                </button>
            </form>
        </div>
    </div>
    
    <!-- JS Success Container -->
    <div id="js-success-section" class="hidden text-center py-12 px-6">
        <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-green-100">
            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-3xl font-black text-[#1A1A3E] mb-2">{{ __('sms_gateway.payment_successful') }}</h3>
        <p class="text-gray-500 font-semibold mb-8 text-[14px]">{{ __('sms_gateway.payment_confirmed_message') }}</p>
        
        <a href="javascript:void(0)" id="success-redirect" target="_top" class="hidden w-full bg-primary text-white font-extrabold py-4 rounded-xl hover:bg-primary-hover transition-all">{{ __('sms_gateway.return_to_merchant') }}</a>
    </div>
    @endif
</div>

@if($state === 'open')
<script>
    const VERIFY_URL = @json($verifyUrl ?? '');
    const STATUS_URL = @json($statusUrl ?? '');
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const EXPIRES_AT = @json($expiresAt ?? null);

    const TX_LABEL = @json(__('sms_gateway.enter_transaction_reference'));
    const TX_PLACEHOLDER = @json(__('sms_gateway.reference_placeholder'));
    const TX_HINT = @json(__('sms_gateway.reference_hint'));

    const SENDER_LABEL = @json(__('sms_gateway.enter_sender_number'));
    const SENDER_PLACEHOLDER = @json(__('sms_gateway.sender_number_placeholder'));
    const SENDER_HINT = @json(__('sms_gateway.sender_number_hint'));

    // ── Method selection ────────────────────────
    function updateStep2UI(isEtisalat) {
        document.getElementById('step2-label-text').textContent = isEtisalat ? SENDER_LABEL : TX_LABEL;
        document.getElementById('ref-input').placeholder = isEtisalat ? SENDER_PLACEHOLDER : TX_PLACEHOLDER;
        
        if(isEtisalat) {
            document.getElementById('ref-input').setAttribute('type', 'tel');
        } else {
            document.getElementById('ref-input').setAttribute('type', 'text');
        }
    }

    document.querySelectorAll('input[name="method"]').forEach(radio => {
        radio.addEventListener('change', e => {
            if(e.target.checked) {
                document.getElementById('wallet-display').textContent = e.target.dataset.phone;
                updateStep2UI(e.target.dataset.isEtisalat === 'true');
            }
        });
    });

    const initialMethod = document.querySelector('input[name="method"]:checked');
    if (initialMethod) {
        updateStep2UI(initialMethod.dataset.isEtisalat === 'true');
    }

    // ── Copy wallet ─────────────────────────────
    function copyWallet() {
        const num = document.getElementById('wallet-display').textContent.trim();
        
        // Use hidden textarea for maximum compatibility
        const textArea = document.createElement("textarea");
        textArea.value = num;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try { document.execCommand('copy'); } catch (err) {}
        document.body.removeChild(textArea);
        
        const btn = document.getElementById('copy-btn');
        const originalText = btn.innerText;
        btn.innerText = '{{ __("sms_gateway.copied") }}';
        btn.classList.add('bg-green-100', 'text-green-700');
        btn.classList.remove('bg-[#E8ECFF]', 'text-primary-hover');
        
        setTimeout(() => {
            btn.innerText = originalText;
            btn.classList.remove('bg-green-100', 'text-green-700');
            btn.classList.add('bg-[#E8ECFF]', 'text-primary-hover');
        }, 2000);
    }



    // ── Submit payment ──────────────────────────
    async function submitPayment(e) {
        e.preventDefault();
        
        const ref = document.getElementById('ref-input').value.trim();
        if (!ref) {
            showError('{{ __("sms_gateway.reference_required") }}');
            return;
        }

        const btn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('btn-spinner');
        
        btnText.style.display = 'none';
        spinner.classList.remove('hidden');
        btn.disabled = true;
        hideError();

        try {
            const method = document.querySelector('input[name="method"]:checked')?.value;
            const res = await fetch(VERIFY_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    transaction_reference: ref,
                    payment_method: method,
                }),
            });

            const data = await res.json();

            if (data.paid) {
                document.getElementById('payment-container').style.display = 'none';
                const successSection = document.getElementById('js-success-section');
                successSection.classList.remove('hidden');
                
                if (data.redirect_url) {
                    const redirectBtn = document.getElementById('success-redirect');
                    redirectBtn.href = data.redirect_url;
                    redirectBtn.classList.remove('hidden');
                    redirectBtn.classList.add('inline-block');
                    setTimeout(() => { window.top.location.href = data.redirect_url; }, 3000);
                }
            } else {
                showError(data.message || '{{ __("sms_gateway.payment_not_found_yet") }}');
            }
        } catch (err) {
            showError('{{ __("sms_gateway.connection_error") }}');
        } finally {
            btnText.style.display = 'block';
            spinner.classList.add('hidden');
            btn.disabled = false;
        }
    }

    function showError(msg) {
        const el = document.getElementById('error-alert');
        el.textContent = msg;
        el.classList.remove('hidden');
    }
    function hideError() {
        document.getElementById('error-alert').classList.add('hidden');
    }

    // ── Auto-poll for status ──
    if(STATUS_URL) {
        setInterval(async () => {
            try {
                const res = await fetch(STATUS_URL);
                const data = await res.json();
                if (data.status === 'complete' && data.redirect_url) {
                    window.top.location.href = data.redirect_url;
                }
            } catch (err) { /* silent */ }
        }, 5000);
    }
</script>
@endif

</body>
</html>
