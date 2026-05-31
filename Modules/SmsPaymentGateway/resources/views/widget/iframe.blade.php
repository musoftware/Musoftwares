<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إتمام الدفع - {{ $merchantName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');
        body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
        .method-label { cursor: pointer; transition: all 0.2s ease; }
        input[type="radio"]:checked + .method-label { border-color: #4f46e5; background-color: #eff6ff; }
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
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<div class="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden border border-gray-100 fade-in" id="payment-container">
    
    <!-- Header -->
    <div class="bg-indigo-600 p-8 text-center text-white relative">
        <h2 class="text-sm font-semibold mb-1 opacity-90">{{ $merchantName }}</h2>
        <p class="text-xs opacity-75 mb-4">يطلب منك دفع مبلغ</p>
        <div class="text-5xl font-black tracking-tight">
            {{ number_format($amount, 2) }} <span class="text-xl font-semibold opacity-80">{{ $currency }}</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-6">
        
        <!-- ALERT MESSAGE -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <h3 class="text-blue-800 font-bold mb-1 text-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                الخطوة الأولى: قم بتحويل المبلغ
            </h3>
            <p class="text-blue-700 text-xs font-semibold leading-relaxed">
                برجاء تحويل المبلغ المطلوب بدقة (<span class="font-bold">{{ number_format($amount, 2) }}</span>) إلى رقم المبيعات التالي:
            </p>
            
            <div class="mt-3 bg-white border border-blue-100 rounded-lg p-3 flex justify-between items-center shadow-sm">
                <div class="font-mono text-xl font-bold tracking-widest text-gray-900" dir="ltr" id="wallet-number">
                    {{ $phone ?? 'غير متوفر' }}
                </div>
                <button type="button" onclick="copyNumber()" class="bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-bold py-1 px-3 rounded transition">
                    نسخ الرقم
                </button>
            </div>
        </div>

        <form id="verify-form" onsubmit="submitVerification(event)">
            <!-- METHOD SELECTION -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-800 mb-3">اختر طريقة التحويل التي استخدمتها:</label>
                <div class="grid grid-cols-2 gap-3">
                    @if(isset($isInstapay) && $isInstapay)
                    <label class="relative block">
                        <input type="radio" name="payment_method" value="instapay" class="peer sr-only" required>
                        <div class="method-label border-2 border-gray-200 rounded-xl p-3 text-center flex flex-col items-center justify-center opacity-70 peer-checked:opacity-100 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:shadow-sm">
                            <img src="https://play-lh.googleusercontent.com/9C0DttWn3kED_0L2OQd-p0R_Q3m1Q13i-M-65wD_lIq806oHh4g6vK1o6L7Kk-b29pU" alt="Instapay" class="h-8 object-contain rounded mb-1">
                            <span class="text-xs font-bold text-gray-700">إنستاباي</span>
                        </div>
                    </label>
                    @endif

                    @if(isset($isVodafone) && $isVodafone)
                    <label class="relative block">
                        <input type="radio" name="payment_method" value="vodafone_cash" class="peer sr-only" required>
                        <div class="method-label border-2 border-gray-200 rounded-xl p-3 text-center flex flex-col items-center justify-center opacity-70 peer-checked:opacity-100 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:shadow-sm">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Vodafone_logo.svg/1200px-Vodafone_logo.svg.png" alt="Vodafone Cash" class="h-8 object-contain mb-1">
                            <span class="text-xs font-bold text-gray-700">فودافون كاش / محافظ</span>
                        </div>
                    </label>
                    @endif
                </div>
            </div>

            <!-- TRANSACTION ID -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-800 mb-2">رقم العملية (Transaction ID):</label>
                <input type="text" id="transaction_id" name="transaction_id" required placeholder="أدخل رقم العملية الذي ظهر لك بعد التحويل"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-transparent text-left font-mono tracking-wider transition shadow-sm" dir="ltr">
                <p class="text-[10px] text-gray-500 mt-2">تجد رقم العملية في رسالة التأكيد أو إيصال التحويل.</p>
            </div>

            <!-- ERROR MSG -->
            <div id="error-message" class="hidden text-sm text-red-600 mb-4 p-3 bg-red-50 rounded-lg border border-red-100 text-center font-bold"></div>

            <!-- SUBMIT -->
            <button type="submit" id="submit-btn" class="w-full bg-gray-900 hover:bg-black text-white font-bold text-lg py-4 px-4 rounded-xl transition flex justify-center items-center shadow-lg">
                <span id="btn-text">تأكيد الدفع</span>
                <div id="btn-spinner" class="spinner hidden mr-3"></div>
            </button>
        </form>
    </div>
</div>

<!-- SUCCESS STATE -->
<div class="hidden bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden border border-gray-100 text-center py-12 px-6 fade-in" id="success-container">
    <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-green-100">
        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
        </svg>
    </div>
    <h3 class="text-3xl font-black text-gray-900 mb-2">تم الدفع بنجاح!</h3>
    <p class="text-gray-500 font-semibold mb-8">تم استلام الدفعة وتأكيد طلبك في نظامنا.</p>
    
    <div class="bg-gray-50 rounded-xl p-4 flex justify-between items-center border border-gray-100 mx-auto max-w-xs mb-8">
        <span class="text-gray-500 text-sm font-bold">رقم العملية</span>
        <span class="font-mono font-bold text-gray-900 text-lg tracking-wider" id="success-tx-id"></span>
    </div>

    @if(isset($redirectUrl) && $redirectUrl)
        <a href="{{ $redirectUrl }}" class="inline-block w-full bg-indigo-600 text-white font-bold py-4 rounded-xl hover:bg-indigo-700 transition">العودة للموقع</a>
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
        
        setTimeout(() => {
            btn.innerText = originalText;
            btn.classList.remove('bg-green-100', 'text-green-700');
        }, 2000);
    }

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
                document.getElementById('payment-container').classList.add('hidden');
                document.getElementById('success-container').classList.remove('hidden');
                document.getElementById('success-tx-id').innerText = data.transaction_id || transactionId;
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
