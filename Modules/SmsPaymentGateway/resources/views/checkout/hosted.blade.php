<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('sms_gateway.checkout_title') }} — {{ $merchantName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f0f2f5;
            --card: #ffffff;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #059669;
            --success-bg: #ecfdf5;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --error: #dc2626;
            --error-bg: #fef2f2;
            --text: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
            --shadow-lg: 0 10px 25px -5px rgb(0 0 0 / 0.08), 0 8px 10px -6px rgb(0 0 0 / 0.04);
        }
        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            color: var(--text);
        }
        .checkout-container {
            width: 100%;
            max-width: 440px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Header ────────────────────────────── */
        .checkout-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 32px 24px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .checkout-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 60%);
        }
        .merchant-name {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 4px;
            position: relative;
        }
        .merchant-label {
            font-size: 12px;
            opacity: 0.7;
            margin-bottom: 16px;
            position: relative;
        }
        .amount-display {
            font-size: 48px;
            font-weight: 900;
            letter-spacing: -1px;
            position: relative;
        }
        .amount-currency {
            font-size: 18px;
            font-weight: 600;
            opacity: 0.8;
            margin-right: 4px;
        }
        .expires-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 12px;
            position: relative;
        }
        .expires-badge .dot {
            width: 6px;
            height: 6px;
            background: #34d399;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* ── Body ──────────────────────────────── */
        .checkout-body { padding: 24px; }

        /* ── Step Section ───────────────────────── */
        .step-section {
            margin-bottom: 20px;
        }
        .step-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 12px;
        }
        .step-number {
            width: 22px;
            height: 22px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            flex-shrink: 0;
        }

        /* ── Payment Methods ───────────────────── */
        .methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        .method-option {
            position: relative;
        }
        .method-option input { position: absolute; opacity: 0; pointer-events: none; }
        .method-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 14px 10px;
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.6;
        }
        .method-option input:checked + .method-card {
            border-color: var(--primary);
            background: #eef2ff;
            opacity: 1;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .method-card:hover { opacity: 0.85; }
        .method-icons { display: flex; align-items: center; justify-content: center; gap: 4px; margin-bottom: 6px; }
        .method-card img { height: 28px; object-fit: contain; border-radius: 4px; }
        .method-card span { font-size: 11px; font-weight: 700; color: var(--text-secondary); text-align: center; }

        /* ── Wallet Number Box ─────────────────── */
        .wallet-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .wallet-number {
            font-family: 'Courier New', monospace;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--text);
            direction: ltr;
        }
        .copy-btn {
            background: #e0e7ff;
            color: var(--primary);
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .copy-btn:hover { background: #c7d2fe; }
        .copy-btn.copied { background: var(--success-bg); color: var(--success); }

        /* ── Input ──────────────────────────────── */
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            direction: ltr;
            text-align: left;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .form-input::placeholder { font-family: 'Cairo', sans-serif; letter-spacing: 0; direction: rtl; text-align: right; }
        .input-hint {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 8px;
        }

        /* ── Alert ──────────────────────────────── */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
            display: none;
        }
        .alert.visible { display: block; animation: fadeIn 0.3s ease; }
        .alert-error { background: var(--error-bg); color: var(--error); border: 1px solid #fecaca; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* ── Submit Button ──────────────────────── */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: #111827;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .submit-btn:hover { background: #000; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .submit-btn:active { transform: translateY(0); }
        .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .submit-btn .spinner {
            width: 18px; height: 18px;
            border: 2.5px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        .submit-btn.loading .spinner { display: block; }
        .submit-btn.loading .btn-text { display: none; }

        /* ── Cancel link ───────────────────────── */
        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
        }
        .cancel-link:hover { color: var(--error); }

        /* ── Footer ─────────────────────────────── */
        .checkout-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-secondary);
        }
        .checkout-footer svg { width: 14px; height: 14px; }

        /* ── Success State ──────────────────────── */
        .success-container { display: none; text-align: center; padding: 48px 24px; }
        .success-container.visible { display: block; }
        .success-icon {
            width: 80px; height: 80px;
            background: var(--success-bg);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            border: 4px solid #a7f3d0;
        }
        .success-icon svg { width: 40px; height: 40px; color: var(--success); }
        .success-title { font-size: 24px; font-weight: 900; color: var(--text); margin-bottom: 8px; }
        .success-subtitle { font-size: 14px; color: var(--text-secondary); margin-bottom: 24px; font-weight: 500; }
        .success-btn {
            display: inline-block;
            padding: 14px 32px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: background 0.2s;
        }
        .success-btn:hover { background: var(--primary-hover); }

        /* ── Expired State ──────────────────────── */
        .expired-container { display: none; text-align: center; padding: 48px 24px; }
        .expired-container.visible { display: block; }
        .expired-icon {
            width: 80px; height: 80px;
            background: var(--warning-bg);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            border: 4px solid #fde68a;
        }
        .expired-title { font-size: 24px; font-weight: 900; color: var(--text); margin-bottom: 8px; }
        .expired-subtitle { font-size: 14px; color: var(--text-secondary); margin-bottom: 24px; font-weight: 500; }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="checkout-container">
    @if($state === 'success')
    {{-- ── SUCCESS STATE ─────────────────────── --}}
    <div class="checkout-header">
        <div class="merchant-name">{{ $merchantName }}</div>
        <div class="amount-display">
            {{ number_format($amount, 2) }}
            <span class="amount-currency">{{ $currency }}</span>
        </div>
    </div>
    <div class="success-container visible">
        <div class="success-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="success-title">{{ __('sms_gateway.payment_successful') }}</div>
        <div class="success-subtitle">{{ __('sms_gateway.payment_confirmed_message') }}</div>
        @if($session->success_url)
        <a href="{{ $session->getSuccessRedirectUrl() }}" class="success-btn" target="_top">{{ __('sms_gateway.return_to_merchant') }}</a>
        @endif
    </div>

    @elseif($state === 'expired')
    {{-- ── EXPIRED STATE ─────────────────────── --}}
    <div class="checkout-header" style="background: linear-gradient(135deg, #d97706, #ea580c);">
        <div class="merchant-name">{{ $merchantName }}</div>
        <div class="amount-display">
            {{ number_format($amount, 2) }}
            <span class="amount-currency">{{ $currency }}</span>
        </div>
    </div>
    <div class="expired-container visible">
        <div class="expired-icon">
            <svg fill="none" stroke="#d97706" viewBox="0 0 24 24" width="40" height="40"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="expired-title">{{ __('sms_gateway.session_expired_title') }}</div>
        <div class="expired-subtitle">{{ __('sms_gateway.session_expired_message') }}</div>
    </div>

    @else
    {{-- ── OPEN STATE (Payment Form) ─────────── --}}
    <div class="checkout-header" id="checkout-header">
        <div class="merchant-name">{{ $merchantName }}</div>
        <div class="merchant-label">{{ __('sms_gateway.requesting_payment') }}</div>
        <div class="amount-display">
            {{ number_format($amount, 2) }}
            <span class="amount-currency">{{ $currency }}</span>
        </div>
        @if(isset($expiresAt))
        <div class="expires-badge">
            <span class="dot"></span>
            <span id="countdown"></span>
        </div>
        @endif
    </div>

    <div class="checkout-body" id="checkout-body">
        {{-- Step 1: Choose method & transfer --}}
        <div class="step-section">
            <div class="step-label">
                <span class="step-number">1</span>
                {{ __('sms_gateway.choose_method_and_transfer') }}
            </div>
            <div class="methods-grid">
                @foreach($paymentMethods as $i => $method)
                <label class="method-option">
                    <input type="radio" name="method" value="{{ $method['id'] }}" data-phone="{{ $method['phone'] }}" data-is-etisalat="{{ isset($method['is_etisalat']) && $method['is_etisalat'] ? 'true' : 'false' }}" {{ $i === 0 ? 'checked' : '' }}>
                    <div class="method-card">
                        <div class="method-icons">
                            <img src="{{ $method['icon'] }}" alt="{{ $method['name'] }}">
                            @if(isset($method['additional_icons']))
                                @foreach($method['additional_icons'] as $icon)
                                    <img src="{{ $icon }}" alt="wallet">
                                @endforeach
                            @endif
                        </div>
                        <span>{{ $method['name'] }}</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Wallet number --}}
        <div class="wallet-box">
            <div class="wallet-number" id="wallet-display">{{ $paymentMethods[0]['phone'] ?? '' }}</div>
            <button type="button" class="copy-btn" id="copy-btn" onclick="copyWallet()">{{ __('sms_gateway.copy') }}</button>
        </div>

        {{-- Step 2: Enter reference --}}
        <div class="step-section">
            <div class="step-label">
                <span class="step-number">2</span>
                <span id="step2-label-text">{{ __('sms_gateway.enter_transaction_reference') }}</span>
            </div>
            <input type="text" id="ref-input" class="form-input" placeholder="{{ __('sms_gateway.reference_placeholder') }}">
            <div class="input-hint" id="step2-hint">{{ __('sms_gateway.reference_hint') }}</div>
        </div>

        {{-- Alert --}}
        <div class="alert alert-error" id="error-alert"></div>

        {{-- Submit --}}
        <button type="button" class="submit-btn" id="submit-btn" onclick="submitPayment()">
            <span class="btn-text">{{ __('sms_gateway.confirm_payment') }}</span>
            <div class="spinner"></div>
        </button>

        @if(isset($cancelUrl) && $cancelUrl)
        <a href="{{ $cancelUrl }}" class="cancel-link">{{ __('sms_gateway.cancel_payment') }}</a>
        @endif
    </div>

    {{-- Success (hidden, shown via JS) --}}
    <div class="success-container" id="success-section">
        <div class="success-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="success-title">{{ __('sms_gateway.payment_successful') }}</div>
        <div class="success-subtitle">{{ __('sms_gateway.payment_confirmed_message') }}</div>
        <a href="javascript:void(0)" class="success-btn" id="success-redirect" style="display:none" target="_top">{{ __('sms_gateway.return_to_merchant') }}</a>
    </div>

    <div class="checkout-footer">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        {{ __('sms_gateway.secure_checkout') }}
    </div>
    @endif
</div>

@if($state === 'open')
<script>
    const VERIFY_URL = @json($verifyUrl);
    const STATUS_URL = @json($statusUrl);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const EXPIRES_AT = @json($expiresAt);

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
        document.getElementById('step2-hint').textContent = isEtisalat ? SENDER_HINT : TX_HINT;
    }

    document.querySelectorAll('input[name="method"]').forEach(radio => {
        radio.addEventListener('change', e => {
            document.getElementById('wallet-display').textContent = e.target.dataset.phone;
            updateStep2UI(e.target.dataset.isEtisalat === 'true');
        });
    });

    const initialMethod = document.querySelector('input[name="method"]:checked');
    if (initialMethod) {
        updateStep2UI(initialMethod.dataset.isEtisalat === 'true');
    }

    // ── Copy wallet ─────────────────────────────
    function copyWallet() {
        const num = document.getElementById('wallet-display').textContent.trim();
        
        // Use hidden textarea for maximum compatibility (iOS, WebViews, iframes)
        const textArea = document.createElement("textarea");
        textArea.value = num;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
        } catch (err) {}
        document.body.removeChild(textArea);

        const btn = document.getElementById('copy-btn');
        btn.textContent = '{{ __("sms_gateway.copied") }}';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = '{{ __("sms_gateway.copy") }}';
            btn.classList.remove('copied');
        }, 2000);
    }

    // ── Countdown timer ─────────────────────────
    if (EXPIRES_AT) {
        const expiryDate = new Date(EXPIRES_AT);
        const countdownEl = document.getElementById('countdown');

        function updateCountdown() {
            const diff = expiryDate - new Date();
            if (diff <= 0) {
                location.reload();
                return;
            }
            const m = Math.floor(diff / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            countdownEl.textContent = `${m}:${String(s).padStart(2, '0')}`;
        }
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // ── Submit payment ──────────────────────────
    async function submitPayment() {
        const ref = document.getElementById('ref-input').value.trim();
        if (!ref) {
            showError('{{ __("sms_gateway.reference_required") }}');
            return;
        }

        const btn = document.getElementById('submit-btn');
        btn.classList.add('loading');
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
                document.getElementById('checkout-header').style.display = 'none';
                document.getElementById('checkout-body').style.display = 'none';
                const successSection = document.getElementById('success-section');
                successSection.classList.add('visible');
                if (data.redirect_url) {
                    const redirectBtn = document.getElementById('success-redirect');
                    redirectBtn.href = data.redirect_url;
                    redirectBtn.style.display = 'inline-block';
                    // Auto-redirect after 3 seconds
                    setTimeout(() => { window.top.location.href = data.redirect_url; }, 3000);
                }
            } else {
                showError(data.message || '{{ __("sms_gateway.payment_not_found_yet") }}');
            }
        } catch (e) {
            showError('{{ __("sms_gateway.connection_error") }}');
        } finally {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }

    function showError(msg) {
        const el = document.getElementById('error-alert');
        el.textContent = msg;
        el.classList.add('visible');
    }
    function hideError() {
        document.getElementById('error-alert').classList.remove('visible');
    }

    // ── Auto-poll for status (in case auto-matched) ──
    setInterval(async () => {
        try {
            const res = await fetch(STATUS_URL);
            const data = await res.json();
            if (data.status === 'complete' && data.redirect_url) {
                window.top.location.href = data.redirect_url;
            }
        } catch (e) { /* silent */ }
    }, 5000);

    // ── Enter key submits ───────────────────────
    document.getElementById('ref-input').addEventListener('keypress', e => {
        if (e.key === 'Enter') submitPayment();
    });
</script>
@endif

</body>
</html>
