@once
<script>
    // Writes an amount in Arabic words the way it is written on invoices and receipts.
    window.FTWords = (() => {
        const ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        const tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        const hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
        const scales = [null, ['ألف', 'ألفان', 'آلاف', 'ألفًا'], ['مليون', 'مليونان', 'ملايين', 'مليونًا'], ['مليار', 'ملياران', 'مليارات', 'مليارًا'], ['تريليون', 'تريليونان', 'تريليونات', 'تريليونًا']];
        const currencies = {
            EGP: { forms: ['جنيه مصري', 'جنيهان مصريان', 'جنيهات مصرية', 'جنيهًا مصريًا'], sub: 100, subName: 'قرش' },
            SAR: { forms: ['ريال سعودي', 'ريالان سعوديان', 'ريالات سعودية', 'ريالًا سعوديًا'], sub: 100, subName: 'هللة' },
            AED: { forms: ['درهم إماراتي', 'درهمان إماراتيان', 'دراهم إماراتية', 'درهمًا إماراتيًا'], sub: 100, subName: 'فلس' },
            KWD: { forms: ['دينار كويتي', 'ديناران كويتيان', 'دنانير كويتية', 'دينارًا كويتيًا'], sub: 1000, subName: 'فلس' },
            QAR: { forms: ['ريال قطري', 'ريالان قطريان', 'ريالات قطرية', 'ريالًا قطريًا'], sub: 100, subName: 'درهم' },
            JOD: { forms: ['دينار أردني', 'ديناران أردنيان', 'دنانير أردنية', 'دينارًا أردنيًا'], sub: 1000, subName: 'فلس' },
            USD: { forms: ['دولار أمريكي', 'دولاران أمريكيان', 'دولارات أمريكية', 'دولارًا أمريكيًا'], sub: 100, subName: 'سنت' },
        };

        function under1000(n) {
            const parts = [], rest = n % 100, unit = rest % 10;
            if (n >= 100) parts.push(hundreds[Math.floor(n / 100)]);
            if (rest >= 20) parts.push((unit ? ones[unit] + ' و' : '') + tens[Math.floor(rest / 10)]);
            else if (rest) parts.push(ones[rest]);
            return parts.join(' و');
        }

        function counted(n, [one, two, few, many]) {
            if (n === 1) return one;
            if (n === 2) return two;
            const rest = n % 100;
            if (rest >= 3 && rest <= 10) return under1000(n) + ' ' + few;
            return under1000(n) + ' ' + (rest >= 11 ? many : one);
        }

        function words(n) {
            if (n === 0) return 'صفر';
            const parts = [];
            for (let level = scales.length - 1; level >= 0; level--) {
                const size = 1000 ** level, group = Math.floor(n / size) % 1000;
                if (group) parts.push(level ? counted(group, scales[level]) : under1000(group));
            }
            return parts.join(' و');
        }

        function unitText(n, forms) {
            if (n === 1) return forms[0] + ' واحد';
            if (n === 2) return forms[1];
            if (n % 1000 === 0) return words(n).replace(/(ألف|مليون|مليار|تريليون)ًا$/, '$1') + ' ' + forms[0];
            const rest = n % 100, form = rest >= 3 && rest <= 10 ? forms[2] : rest >= 11 ? forms[3] : forms[0];
            return words(n) + ' ' + form;
        }

        function amount(value, code) {
            const currency = currencies[code], total = Math.round(value * currency.sub);
            const whole = Math.floor(total / currency.sub), fraction = total % currency.sub;
            const text = whole || !fraction ? unitText(whole, currency.forms) : '';
            const small = fraction ? (text ? ' و' : '') + fraction + '/' + currency.sub + ' ' + currency.subName : '';
            return 'فقط ' + text + small + ' لا غير';
        }

        return { amount, words };
    })();
</script>
@endonce
