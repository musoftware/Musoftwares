import os, re, json

files = [os.path.join('resources/js/Pages/SmsPaymentGateway', f) for f in os.listdir('resources/js/Pages/SmsPaymentGateway') if f.endswith('.tsx')]
files.append('resources/js/Pages/TextPaymentGateway.tsx')

translations = set()
for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        matches = re.finditer(r'[\'\"\>]([^\'\"\<\>]*?[\u0600-\u06FF]+[^\'\"\<\>]*?)[\'\"\<]', f.read())
        for m in matches:
            t = m.group(1).strip()
            if t: translations.add(t)

with open('arabic_strings.json', 'w', encoding='utf-8') as f:
    json.dump(list(translations), f, ensure_ascii=False, indent=2)
