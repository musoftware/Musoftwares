<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        .spinner { border: 4px solid rgba(0,0,0,0.1); border-left-color: #2563eb; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

<div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden border border-gray-100">
    <div class="bg-blue-600 p-6 text-center text-white">
        <h2 class="text-xl font-bold">بوابة الدفع الآمنة</h2>
        <p class="text-blue-100 text-sm mt-1">{{ $merchantName }}</p>
    </div>

    <div class="p-6" id="payment-container">
        <div class="text-center mb-6">
            <p class="text-gray-500 text-sm font-semibold mb-1">المبلغ المطلوب</p>
            <p class="text-4xl font-bold text-gray-800">{{ $amount }} <span class="text-lg text-gray-500">ج.م</span></p>
        </div>

        <div class="bg-blue-50 rounded-xl p-4 mb-6 border border-blue-100">
            <h3 class="font-bold text-blue-800 mb-2">تعليمات الدفع:</h3>
            <ul class="text-sm text-blue-700 space-y-2 list-disc list-inside">
                <li>قم بتحويل المبلغ المطلوب بالضبط.</li>
                @if($reference)
                <li>يجب كتابة الرقم المرجعي <span class="font-bold bg-blue-200 px-1 rounded">{{ $reference }}</span> في سبب التحويل.</li>
                @endif
                @if($phone)
                <li>يجب أن يتم التحويل من الرقم: <span class="font-bold" dir="ltr">{{ $phone }}</span>.</li>
                @endif
            </ul>
        </div>

        <div class="flex items-center justify-center space-x-3 space-x-reverse bg-gray-50 p-4 rounded-xl">
            <div class="spinner"></div>
            <p class="text-gray-600 font-semibold text-sm">في انتظار وصول التحويل...</p>
        </div>
        <p class="text-center text-xs text-gray-400 mt-4">لا تقم بإغلاق هذه الصفحة حتى تكتمل العملية.</p>
    </div>

    <div class="hidden p-8 text-center" id="success-container">
        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">تم الدفع بنجاح!</h3>
        <p class="text-gray-500 text-sm mb-6">تم استلام المبلغ وتأكيد العملية.</p>
        @if($redirectUrl)
            <a href="{{ $redirectUrl }}" class="inline-block w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-blue-700 transition">العودة للموقع</a>
        @endif
    </div>
</div>

<script>
    const pollingUrl = '{{ $pollingUrl }}';
    const redirectUrl = '{!! $redirectUrl !!}';
    let pollInterval;

    function checkStatus() {
        fetch(pollingUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.paid) {
                    clearInterval(pollInterval);
                    document.getElementById('payment-container').classList.add('hidden');
                    document.getElementById('success-container').classList.remove('hidden');
                    
                    if (redirectUrl) {
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 3000);
                    }
                }
            })
            .catch(error => console.error('Error polling status:', error));
    }

    // Poll every 5 seconds
    pollInterval = setInterval(checkStatus, 5000);
</script>

</body>
</html>
