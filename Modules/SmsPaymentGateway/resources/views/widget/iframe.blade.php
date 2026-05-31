<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إتمام عملية الدفع - {{ $merchantName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; background-color: #f3f4f6; }
        .spinner { border: 3px solid rgba(255,255,255,0.3); border-left-color: #fff; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .method-card { transition: all 0.2s; cursor: pointer; }
        .method-card.selected { border-color: #2563eb; background-color: #eff6ff; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

<div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-gray-100">
    <!-- Header -->
    <div class="bg-gray-900 p-6 text-center text-white relative">
        <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold">بوابة الدفع الآمنة</h2>
        <p class="text-gray-300 text-sm mt-1">{{ $merchantName }}</p>
    </div>

    <!-- Main Content -->
    <div class="p-6" id="payment-container">
        <!-- Amount -->
        <div class="text-center mb-6 pb-6 border-b border-gray-100">
            <p class="text-gray-500 text-sm font-semibold mb-1">المبلغ المطلوب للدفع</p>
            <p class="text-4xl font-bold text-gray-900">{{ number_format($amount, 2) }} <span class="text-lg text-gray-500">{{ $currency }}</span></p>
            @if($reference)
                <p class="text-xs text-gray-400 mt-2">رقم الطلب: {{ $reference }}</p>
            @endif
        </div>

        <!-- Method Selection -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-gray-700 mb-3">اختر طريقة الدفع:</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="method-card border-2 border-gray-200 rounded-xl p-3 text-center flex flex-col items-center justify-center" onclick="selectMethod('Wallet')">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Vodafone_logo.svg/1200px-Vodafone_logo.svg.png" alt="Vodafone Cash" class="h-8 object-contain mb-2">
                    <span class="text-sm font-semibold text-gray-700">محافظ إلكترونية</span>
                </div>
                <div class="method-card border-2 border-gray-200 rounded-xl p-3 text-center flex flex-col items-center justify-center" onclick="selectMethod('Instapay')">
                    <img src="https://play-lh.googleusercontent.com/9C0DttWn3kED_0L2OQd-p0R_Q3m1Q13i-M-65wD_lIq806oHh4g6vK1o6L7Kk-b29pU" alt="Instapay" class="h-8 object-contain rounded mb-2">
                    <span class="text-sm font-semibold text-gray-700">إنستاباي</span>
                </div>
            </div>
        </div>

        <!-- Dynamic Instructions Area -->
        <div id="instructions-area" class="hidden mb-6">
            <!-- Vodafone Warning -->
            <div id="wallet-warning" class="hidden bg-red-50 border-r-4 border-red-500 p-3 mb-4 rounded-l-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm font-bold text-red-800">تنبيه هام جداً!</p>
                        <p class="text-xs text-red-700 mt-1">يجب التحويل من محفظة إلكترونية (فودافون كاش، أورانج كاش، الخ) وليس من انستاباي.</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 mb-4">
                <p class="text-sm text-gray-600 mb-2">قم بتحويل المبلغ إلى <span id="account-type-label"></span>:</p>
                
                <!-- Accounts List -->
                <div id="accounts-list" class="space-y-2">
                    <!-- Populated via JS -->
                </div>
            </div>

            <!-- Verification Form -->
            <form id="verify-form" onsubmit="submitVerification(event)">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="transaction_id">
                        رقم العملية (Transaction ID) أو رقم هاتفك
                    </label>
                    <input type="text" id="transaction_id" required placeholder="أدخل رقم العملية الذي ظهر لك بعد التحويل..."
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-left" dir="ltr">
                    <p class="text-xs text-gray-500 mt-2">نحتاج لرقم العملية أو رقم الهاتف الذي قمت بالتحويل منه لتأكيد الدفع.</p>
                </div>

                <div id="error-message" class="hidden text-sm text-red-600 mb-4 text-center font-semibold"></div>

                <button type="submit" id="submit-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex justify-center items-center h-12 shadow-md shadow-blue-500/30">
                    <span id="btn-text">تحقق من الدفع الآن</span>
                    <div id="btn-spinner" class="spinner hidden mr-2"></div>
                </button>
            </form>
        </div>
    </div>

    <!-- Success State -->
    <div class="hidden p-8 text-center" id="success-container">
        <div class="w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-green-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">تم الدفع بنجاح!</h3>
        <p class="text-gray-500 text-sm mb-8">تم استلام المبلغ وتأكيد العملية في نظامنا. شكراً لك.</p>
        @if($redirectUrl)
            <a href="{{ $redirectUrl }}" class="inline-block w-full bg-gray-900 text-white font-bold py-3 px-4 rounded-xl hover:bg-black transition">العودة للموقع</a>
        @endif
    </div>
</div>

<script>
    const verifyUrl = '{{ $verifyUrl }}';
    const redirectUrl = '{!! $redirectUrl !!}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Wallets data injected from backend
    const wallets = @json($wallets);
    let selectedMethod = null;

    function selectMethod(method) {
        selectedMethod = method;
        
        // Update UI styling
        document.querySelectorAll('.method-card').forEach(card => card.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        
        // Show instructions area
        document.getElementById('instructions-area').classList.remove('hidden');
        document.getElementById('error-message').classList.add('hidden');
        
        // Toggle specific warnings
        const isWallet = method === 'Wallet';
        document.getElementById('wallet-warning').classList.toggle('hidden', !isWallet);
        document.getElementById('account-type-label').innerText = isWallet ? 'أحد الأرقام التالية' : 'أحد حسابات إنستاباي التالية';
        
        // Populate accounts
        const accountsList = document.getElementById('accounts-list');
        accountsList.innerHTML = '';
        
        const methodWallets = wallets[method] || [];
        
        if (methodWallets.length === 0) {
            accountsList.innerHTML = '<p class="text-sm text-red-500">عذراً، لا توجد حسابات متاحة لهذه الطريقة حالياً.</p>';
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            document.getElementById('submit-btn').disabled = false;
            document.getElementById('submit-btn').classList.remove('opacity-50', 'cursor-not-allowed');
            
            methodWallets.forEach(wallet => {
                accountsList.innerHTML += `
                    <div class="bg-white border border-gray-200 rounded p-3 flex justify-between items-center cursor-pointer hover:bg-gray-50" onclick="copyToClipboard('${wallet.phone_number}')">
                        <span class="font-bold text-gray-800 tracking-wider" dir="ltr">${wallet.phone_number}</span>
                        <span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded">نسخ</span>
                    </div>
                `;
            });
        }
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Very simple visual feedback
            const el = document.createElement('div');
            el.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-4 py-2 rounded-lg text-sm z-50';
            el.innerText = 'تم النسخ!';
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 2000);
        });
    }

    async function submitVerification(e) {
        e.preventDefault();
        
        const transactionId = document.getElementById('transaction_id').value.trim();
        if (!transactionId) return;

        const btn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('btn-spinner');
        const errorDiv = document.getElementById('error-message');
        
        // Set loading state
        btn.disabled = true;
        btnText.innerText = 'جاري التحقق...';
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
                    order_number: '{{ $order_number }}'
                })
            });

            const data = await response.json();

            if (data.success && data.paid) {
                // Payment verified
                document.getElementById('payment-container').classList.add('hidden');
                document.getElementById('success-container').classList.remove('hidden');
                
                if (redirectUrl) {
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 3000);
                }
            } else {
                // Verification failed (not found)
                errorDiv.innerText = data.message || 'لم نتمكن من إيجاد التحويل. الرجاء التأكد من الرقم والمحاولة مرة أخرى.';
                errorDiv.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error verifying payment:', error);
            errorDiv.innerText = 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.';
            errorDiv.classList.remove('hidden');
        } finally {
            // Reset loading state
            btn.disabled = false;
            btnText.innerText = 'تحقق من الدفع الآن';
            spinner.classList.add('hidden');
        }
    }
</script>

</body>
</html>
