<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>بوابة الدفع - {{ $merchantName }}</title>
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
        .method-card.selected {
            border-color: #4B43D6;
            box-shadow: 0 0 0 3px rgba(75, 67, 214, 0.15);
            background-color: #F6F6FF;
        }
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3); 
            border-top-color: #fff; 
            border-radius: 50%; 
            width: 20px; 
            height: 20px; 
            animation: spin 1s linear infinite; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        /* hide up/down arrows for number input if we ever use it */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<div id="payment-container" class="bg-white rounded-[22px] overflow-hidden shadow-[0_8px_40px_rgba(75,67,214,0.13)] w-full max-w-md zoom-[0.85] fade-in">
    
    <!-- Header -->
    <div class="bg-primary pt-[20px] px-[20px] pb-[24px] text-center text-white relative">
        <div class="text-[17px] font-bold tracking-[0.3px] mb-1 text-white">{{ $merchantName }}</div>
        <div class="text-[14px] font-medium text-white/80 mb-5">يطلب منك دفع مبلغ</div>

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
                <span class="text-[14px] font-extrabold text-[#1A1A3E]">الخطوة الأولى: قم بتحويل المبلغ</span>
            </div>
            <p class="text-[11.5px] text-[#555570] mb-3 text-start leading-relaxed font-medium tracking-tight">برجاء تحويل المبلغ المطلوب بدقة (<span class="font-bold">{{ number_format($amount, 2) }}</span>) إلى رقم المبيعات التالي:</p>
            
            <div class="bg-white border border-[#DDE2F5] rounded-xl px-3 py-2.5 flex items-center justify-between">
                <span class="text-[18px] font-extrabold text-[#1A1A3E] tracking-wider" dir="ltr" id="wallet-number">{{ $phone ?? 'غير متوفر' }}</span>
                <button type="button" onclick="copyNumber()" id="copy-btn" class="bg-[#E8ECFF] hover:bg-[#D4DAFF] active:bg-[#C2CCFF] active:scale-95 text-primary-hover font-bold text-[12px] py-[6px] px-[14px] rounded-[8px] transition-all">نسخ</button>
            </div>
        </div>

        <form id="verify-form" onsubmit="submitVerification(event)" class="flex flex-col gap-4">
            
            <!-- Step 2 -->
            <div class="flex flex-col">
                <div class="flex items-center justify-start gap-2 mb-2.5">
                    <div class="w-[26px] h-[26px] bg-primary text-white rounded-full flex items-center justify-center text-[13px] font-extrabold shrink-0">2</div>
                    <span class="text-[14px] font-extrabold text-[#1A1A3E]">اختر طريقة التحويل التي استخدمتها:</span>
                </div>

                <div class="flex gap-3">
                    @if(isset($isInstapay) && $isInstapay)
                    <label class="flex-1 cursor-pointer group">
                        <input type="radio" name="payment_method" value="instapay" data-phone="{{ $instapayPhone ?? ($phone ?? '') }}" data-is-etisalat="{{ isset($isEtisalatInstapay) && $isEtisalatInstapay ? 'true' : 'false' }}" class="peer sr-only payment-method-radio" required>
                        <div class="method-card peer-checked:border-primary peer-checked:bg-[#F6F6FF] peer-checked:shadow-[0_0_0_3px_rgba(75,67,214,0.15)] bg-white border-[1.5px] border-[#E4E8F5] rounded-[14px] p-3 flex flex-col items-center justify-center min-h-[75px] transition-all group-hover:border-[#9898D8]">
                            <img src="{{ $instapayLogo ?? asset('assets/images/gateways/instapay.png') }}" alt="انستاباي" class="h-[28px] max-w-[77px] object-contain mb-1">
                            <span class="text-[12px] font-bold text-[#444460] mt-0.5">انستاباي</span>
                        </div>
                    </label>
                    @endif

                    @if(isset($isVodafone) && $isVodafone)
                    <label class="flex-[1.45] cursor-pointer group">
                        <input type="radio" name="payment_method" value="vodafone_cash" data-phone="{{ $vodafonePhone ?? ($phone ?? '') }}" data-is-etisalat="{{ isset($isEtisalatVodafone) && $isEtisalatVodafone ? 'true' : 'false' }}" class="peer sr-only payment-method-radio" required>
                        <div class="method-card peer-checked:border-primary peer-checked:bg-[#F6F6FF] peer-checked:shadow-[0_0_0_3px_rgba(75,67,214,0.15)] bg-white border-[1.5px] border-[#E4E8F5] rounded-[14px] p-2 flex flex-col items-center justify-center min-h-[70px] transition-all group-hover:border-[#9898D8]">
                            <div class="flex items-center justify-center gap-1.5 flex-nowrap mb-1.5 w-full overflow-hidden">
                                <img src="{{ $wePayLogo ?? asset('assets/images/gateways/we-pay.png') }}" alt="وي باي" class="h-[28px] shrink object-contain">
                                <img src="{{ $vodafoneCashLogo ?? asset('assets/images/gateways/vodafone-cash.svg') }}" alt="فودافون كاش" class="h-[28px] shrink object-contain">
                                <img src="{{ $orangeCashLogo ?? asset('assets/images/gateways/orange-cash.png') }}" alt="أورنج كاش" class="h-[28px] shrink object-contain">
                                <img src="{{ $etisalatCashLogo ?? asset('assets/images/gateways/etisalat-cash.png') }}" alt="اتصالات كاش" class="h-[28px] shrink object-contain">
                            </div>
                            <span class="text-[12px] font-bold text-[#444460]">المحافظ الإلكترونية</span>
                        </div>
                    </label>
                    @endif
                </div>
            </div>

            <!-- Step 3 -->
            <div class="flex flex-col">
                <div class="flex items-center justify-start gap-2 mb-2.5">
                    <div class="w-[26px] h-[26px] bg-primary text-white rounded-full flex items-center justify-center text-[13px] font-extrabold shrink-0">3</div>
                    <span class="text-[14px] font-extrabold text-[#1A1A3E]" id="tx-label-text">رقم العملية (Transaction ID)</span>
                </div>
                <input type="text" id="transaction_id" name="transaction_id" required 
                       placeholder="أدخل رقم العملية الذي ظهر لك بعد التحويل"
                       class="w-full py-[14px] px-[16px] border-[1.5px] border-[#E4E8F5] rounded-[13px] text-[14px] bg-white text-end font-medium text-[#1A1A2E] outline-none transition-all focus:border-primary focus:shadow-[0_0_0_3px_rgba(75,67,214,0.1)] placeholder:text-[#A8A8BC] placeholder:text-[13.5px] placeholder:font-medium" dir="rtl">
            </div>

            <!-- ERROR MSG -->
            <div id="error-message" class="hidden text-[13px] text-red-600 p-3 bg-red-50 rounded-xl border border-red-100 text-center font-bold"></div>

            <button type="submit" id="submit-btn" class="w-full py-[14px] bg-[#111220] hover:opacity-90 active:scale-95 text-white border-none rounded-[14px] text-[16px] font-extrabold tracking-[0.3px] cursor-pointer transition-all mt-1 flex justify-center items-center shadow-md">
                <span id="btn-text">تأكيد الدفع</span>
                <div id="btn-spinner" class="spinner hidden me-3"></div>
            </button>
        </form>
    </div>
</div>

<!-- SUCCESS STATE -->
<div class="hidden bg-white rounded-[22px] shadow-[0_8px_40px_rgba(75,67,214,0.13)] w-full max-w-md overflow-hidden text-center py-12 px-6 fade-in border border-gray-100" id="success-container">
    <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-green-100">
        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
        </svg>
    </div>
    <h3 class="text-3xl font-black text-[#1A1A3E] mb-2">تم الدفع بنجاح!</h3>
    <p class="text-gray-500 font-semibold mb-8 text-[14px]">تم استلام الدفعة وتأكيد طلبك في نظامنا.</p>
    
    <div class="bg-[#F6F6FF] rounded-xl p-4 flex justify-between items-center border border-[#E4E8F5] mx-auto max-w-xs mb-8">
        <span class="text-primary text-[13px] font-extrabold">رقم العملية</span>
        <span class="font-mono font-bold text-[#1A1A3E] text-lg tracking-wider" id="success-tx-id"></span>
    </div>

    @if(isset($redirectUrl) && $redirectUrl)
        <a href="{{ $redirectUrl }}" target="_top" class="inline-block w-full bg-primary text-white font-extrabold py-4 rounded-xl hover:bg-primary-hover transition-all">العودة للموقع</a>
    @endif
</div>

<!-- EXPIRED STATE -->
<div class="hidden bg-white rounded-[22px] shadow-[0_8px_40px_rgba(75,67,214,0.13)] w-full max-w-md overflow-hidden text-center py-12 px-6 fade-in border border-gray-100" id="expired-container">
    <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-red-100">
        <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>
    <h3 class="text-2xl font-black text-[#1A1A3E] mb-2">انتهت الجلسة</h3>
    <p class="text-gray-500 font-semibold mb-8 text-[14px]">لقد انتهى الوقت المخصص لإتمام عملية الدفع. يرجى المحاولة مرة أخرى.</p>
    
    @if(isset($redirectUrl) && $redirectUrl)
        <a href="{{ $redirectUrl }}" target="_top" class="inline-block w-full bg-[#111220] text-white font-extrabold py-4 rounded-xl hover:opacity-90 transition-all">العودة للموقع</a>
    @endif
</div>

<script>
    const verifyUrl = '{{ $verifyUrl }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    

    function copyNumber() {
        const number = document.getElementById('wallet-number').innerText.trim();
        navigator.clipboard.writeText(number);
        
        const btn = event.currentTarget;
        const originalText = btn.innerText;
        btn.innerText = 'تم النسخ!';
        btn.classList.add('bg-green-100', 'text-green-700');
        btn.classList.remove('bg-[#E8ECFF]', 'text-primary-hover');
        
        setTimeout(() => {
            btn.innerText = originalText;
            btn.classList.remove('bg-green-100', 'text-green-700');
            btn.classList.add('bg-[#E8ECFF]', 'text-primary-hover');
        }, 2000);
    }

    const TX_LABEL_AR = "رقم العملية (Transaction ID)";
    const TX_PLACEHOLDER_AR = "أدخل رقم العملية الذي ظهر لك بعد التحويل";
    
    const SENDER_LABEL_AR = "أدخل رقم الهاتف المرسل";
    const SENDER_PLACEHOLDER_AR = "رقم الموبايل الذي قمت بالتحويل منه";

    function updateFormForMethod(methodId, isEtisalat) {
        document.getElementById('wallet-number').textContent = walletNumbers[methodId] || 'غير متوفر';
        
        document.getElementById('tx-label-text').textContent = isEtisalat ? SENDER_LABEL_AR : TX_LABEL_AR;
        document.getElementById('transaction_id').placeholder = isEtisalat ? SENDER_PLACEHOLDER_AR : TX_PLACEHOLDER_AR;
        
        // Ensure input opens number pad on mobile if it expects a phone number
        if(isEtisalat) {
            document.getElementById('transaction_id').setAttribute('type', 'tel');
        } else {
            document.getElementById('transaction_id').setAttribute('type', 'text');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const radios = document.querySelectorAll('.payment-method-radio');
        const walletNumberDisplay = document.getElementById('wallet-number');
        
        radios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.checked && e.target.dataset.phone) {
                    walletNumberDisplay.innerText = e.target.dataset.phone;
                    updateStep2UI(e.target.dataset.isEtisalat === 'true');
                }
            });
        });
        
        const firstAvailable = document.querySelector('.payment-method-radio');
        if (firstAvailable && !document.querySelector('.payment-method-radio:checked')) {
            firstAvailable.checked = true;
            if (firstAvailable.dataset.phone) {
                walletNumberDisplay.innerText = firstAvailable.dataset.phone;
            }
            updateStep2UI(firstAvailable.dataset.isEtisalat === 'true');
        } else if (document.querySelector('.payment-method-radio:checked')) {
            const checkedRadio = document.querySelector('.payment-method-radio:checked');
            updateStep2UI(checkedRadio.dataset.isEtisalat === 'true');
        }
    });

    async function submitVerification(e) {
        e.preventDefault();
        
        const transactionId = document.getElementById('transaction_id').value.trim();
        const method = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (!transactionId) return;

        const btn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('btn-spinner');
        const errorDiv = document.getElementById('error-message');
        
        btn.disabled = true;
        btnText.innerText = 'جاري المطابقة...';
        spinner.classList.remove('hidden');
        errorDiv.classList.add('hidden');

        try {
            const response = await fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 
                    transaction_id: transactionId,
                    method: method 
                })
            });

            const data = await response.json();

            if (data.success && data.paid) {
                clearInterval(timerInterval);
                document.getElementById('payment-container').classList.add('hidden');
                document.getElementById('success-container').classList.remove('hidden');
                document.getElementById('success-tx-id').innerText = data.transaction_id || transactionId;
                
                if (data.redirect_url) {
                    setTimeout(() => { window.top.location.href = data.redirect_url; }, 3000);
                }
            } else {
                errorDiv.innerText = data.message || 'عذراً، لم نتمكن من إيجاد تحويل بهذا الرقم. تأكد من الرقم أو انتظر دقيقة وحاول مجدداً.';
                errorDiv.classList.remove('hidden');
            }
        } catch (error) {
            errorDiv.innerText = 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.';
            errorDiv.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btnText.innerText = 'تأكيد الدفع';
            spinner.classList.add('hidden');
        }
    }
</script>
</body>
</html>
