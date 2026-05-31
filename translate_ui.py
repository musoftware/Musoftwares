import os, re

files = [os.path.join('resources/js/Pages/SmsPaymentGateway', f) for f in os.listdir('resources/js/Pages/SmsPaymentGateway') if f.endswith('.tsx')]
files.append('resources/js/Pages/TextPaymentGateway.tsx')

translations = {
    "مثال: user@instapay": "Example: user@instapay",
    "تم نسخ الرابط": "Link copied",
    "روابط الدفع السريعة": "Quick Payment Links",
    "السماح للمشتري باختيار إنستاباي كوسيلة تحويل.": "Allow buyer to select Instapay as transfer method.",
    "مثال: أحمد محمد": "Example: Ahmed Mohamed",
    "ضبط رقم هاتف استقبال الأموال وتفعيل خدمات الدفع المتاحة.": "Set the receiving phone number and enable available payment services.",
    "رابط جديد": "New Link",
    "مثال: 01012345678": "Example: 01012345678",
    "المبلغ": "Amount",
    "مدفوع": "Paid",
    "اسم العميل (اختياري)": "Customer Name (optional)",
    "نسخ الرابط": "Copy Link",
    "قم بضبط رقم التحويل وطرق الدفع المتاحة لعملائك.": "Configure transfer number and available payment methods for your customers.",
    "إنشاء رابط جديد": "Create New Link",
    "يستخدم هذا الرقم بشكل افتراضي في حال لم يتم تحديد رقم مخصص لكل طريقة دفع.": "This number is used by default if a specific number is not set for each payment method.",
    "قم بإنشاء روابط دفع سريعة للعملاء لتحصيل المبالغ المطلوبة.": "Create quick payment links for customers to collect required amounts.",
    "فودافون كاش / محافظ": "Vodafone Cash / Wallets",
    "فودافون كاش / المحافظ": "Vodafone Cash / Wallets",
    "طرق الدفع المتاحة والأرقام المخصصة": "Available payment methods and dedicated numbers",
    "الرقم الأساسي (للمحافظ وإنستاباي)": "Primary Number (for Wallets & Instapay)",
    "حفظ الإعدادات": "Save Settings",
    "مثال: 500": "Example: 500",
    "تم إنشاء الرابط بنجاح": "Link created successfully",
    "تم حفظ الإعدادات بنجاح": "Settings saved successfully",
    "هذا هو الرقم الذي سيظهر للمشتري ليقوم بتحويل المبلغ إليه.": "This is the number that will appear to the buyer to transfer the amount to.",
    "إجراءات": "Actions",
    "إدارة الأجهزة": "Manage Devices",
    "روابط الدفع": "Payment Links",
    "إعدادات بوابة الدفع": "Payment Gateway Settings",
    "العميل": "Customer",
    "رقم المحفظة المخصص (اختياري)": "Dedicated Wallet Number (optional)",
    "قم بإنشاء روابط دفع فورية لمشاركتها مع عملائك.": "Create instant payment links to share with your customers.",
    "بيانات التحويل": "Transfer Details",
    "إلغاء": "Cancel",
    "إنشاء رابط دفع": "Create Payment Link",
    "السماح باختيار المحافظ الإلكترونية.": "Allow selecting electronic wallets.",
    "اربط هاتف الأندرويد لقراءة رسائل فودافون كاش وإنستاباي.": "Connect Android phone to read Vodafone Cash and Instapay messages.",
    "لا توجد روابط دفع سابقة. قم بإنشاء أول رابط الآن!": "No previous payment links. Create your first link now!",
    "رقم الطلب": "Order Number",
    "إعدادات البوابة": "Gateway Settings",
    "المبلغ المطلوب (ج.م)": "Required Amount (EGP)",
    "إنستاباي (Instapay)": "Instapay",
    "توليد الرابط": "Generate Link",
    "رقم/عنوان إنستاباي المخصص (اختياري)": "Dedicated Instapay Number/Address (optional)",
    "الإعدادات": "Settings",
    "الحالة": "Status",
    "الأجهزة": "Devices",
    "معلق": "Pending"
}

def replace_in_file(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    original = content

    for ar, en in translations.items():
        # Case 1: >ArabicText<
        content = re.sub(r'>\s*' + re.escape(ar) + r'\s*<', f'>{{__(\'{en}\')}}<', content)
        # Case 2: placeholder="ArabicText" or title="ArabicText"
        content = re.sub(r'([a-zA-Z0-9_]+)="(' + re.escape(ar) + r')"(\s|>|/)', rf'\1={{__(\'{en}\')}}\3', content)
        # Case 3: 'ArabicText' or "ArabicText" inside JS (like toast.success('...'))
        content = re.sub(r'[\'"]' + re.escape(ar) + r'[\'"]', rf"__('{en}')", content)

    if content != original:
        # Check if __ is imported from @/lib/i18n
        if "import { __ }" not in content and "from '@/lib/i18n'" not in content:
            # Insert import at the top
            import_statement = "import { __ } from '@/lib/i18n';\n"
            content = import_statement + content
            
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {file_path}")

for file in files:
    if os.path.exists(file):
        replace_in_file(file)

