@php $f = $copy['form']; @endphp
<style>
    .inv-field { width: 100%; border: 0; border-bottom: 1px dashed rgba(0,0,0,.15); padding: 6px 2px; background: transparent; font-size: 14px; color: #1d1d1f; resize: vertical; }
    .inv-field:focus { outline: none; border-bottom-color: #0071e3; }
    @media print {
        body * { visibility: hidden !important; }
        #inv-sheet, #inv-sheet * { visibility: visible !important; }
        #inv-sheet { position: absolute; inset: 0 0 auto 0; padding: 24px; }
        #inv-sheet .no-print { display: none !important; }
        #inv-sheet .inv-field { border: 0 !important; resize: none; }
        #inv-sheet select { appearance: none; border: 0; }
        @page { margin: 12mm; }
    }
</style>
<div class="no-print flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <label class="text-sm font-semibold" for="inv-currency">{{ __('free_tools.ui.currency') }}</label>
        <select id="inv-currency" class="ft-input !w-auto !py-2">
            @foreach (['EGP', 'SAR', 'AED', 'KWD', 'QAR', 'USD', 'EUR'] as $currency)
                <option>{{ $currency }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="button" id="inv-new" class="ft-btn ft-btn-light"><i class="ri-file-add-line" aria-hidden="true"></i>{{ $f['new'] }}</button>
        <button type="button" id="inv-print" class="ft-btn"><i class="ri-printer-line" aria-hidden="true"></i>{{ __('free_tools.ui.print') }}</button>
    </div>
</div>

<div id="inv-sheet" class="text-[#1d1d1f]">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
        <div class="flex-1">
            <select id="inv-type" class="text-2xl sm:text-3xl font-bold bg-transparent border-0 p-0 pe-10 focus:ring-0" aria-label="{{ $f['type'] }}">
                @foreach ($f['types'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <label class="ft-label mt-4" for="inv-from">{{ $f['from'] }}</label>
            <textarea id="inv-from" class="inv-field" rows="3" placeholder="{{ $f['from_ph'] }}" data-keep></textarea>
        </div>
        <div class="sm:w-56 space-y-3">
            <div><label class="ft-label" for="inv-number">{{ $f['number'] }}</label><input id="inv-number" class="inv-field" dir="ltr"></div>
            <div><label class="ft-label" for="inv-date">{{ $f['date'] }}</label><input id="inv-date" type="date" class="inv-field" dir="ltr"></div>
        </div>
    </div>

    <div class="mt-6">
        <label class="ft-label" for="inv-to">{{ $f['to'] }}</label>
        <textarea id="inv-to" class="inv-field" rows="3" placeholder="{{ $f['to_ph'] }}"></textarea>
    </div>

    <div class="mt-8">
        <div class="hidden sm:grid grid-cols-12 gap-2 border-b border-black/10 pb-2 text-xs font-semibold text-[#1d1d1f]/60">
            <span class="col-span-6">{{ $f['item'] }}</span><span class="col-span-2">{{ $f['qty'] }}</span><span class="col-span-2">{{ $f['price'] }}</span><span class="col-span-2 text-end">{{ $f['line_total'] }}</span>
        </div>
        <div id="inv-items"></div>
        <button type="button" id="inv-add" class="no-print mt-3 text-sm font-semibold text-[#0071e3]"><i class="ri-add-line" aria-hidden="true"></i> {{ $f['add_item'] }}</button>
    </div>

    <div class="mt-8 flex flex-col sm:flex-row justify-between gap-6">
        <div class="flex-1">
            <label class="ft-label" for="inv-notes">{{ $f['notes'] }}</label>
            <textarea id="inv-notes" class="inv-field" rows="3" placeholder="{{ $f['notes_ph'] }}" data-keep></textarea>
        </div>
        <dl class="sm:w-72 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt>{{ $f['subtotal'] }}</dt><dd id="inv-subtotal" dir="ltr">0</dd></div>
            <div class="flex justify-between items-center gap-4"><dt>{{ $f['discount'] }}</dt><dd><input id="inv-discount" class="inv-field !w-24 text-end" inputmode="decimal" dir="ltr" value="0"></dd></div>
            <div class="flex justify-between items-center gap-4"><dt>{{ $f['vat'] }}</dt><dd><input id="inv-vat" class="inv-field !w-24 text-end" inputmode="decimal" dir="ltr" value="14" data-keep></dd></div>
            <div class="flex justify-between gap-4"><dt>{{ $f['vat_amount'] }}</dt><dd id="inv-vat-amount" dir="ltr">0</dd></div>
            <div class="flex justify-between gap-4 border-t border-black/10 pt-3 text-lg font-bold"><dt>{{ $f['grand_total'] }}</dt><dd id="inv-total" dir="ltr">0</dd></div>
        </dl>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const KEY = 'musoftware_invoice_v1';
        const fields = ['inv-type', 'inv-from', 'inv-to', 'inv-number', 'inv-date', 'inv-notes', 'inv-discount', 'inv-vat', 'inv-currency'];
        let items = [];

        function rowHtml(item, i) {
            return `<div class="grid grid-cols-12 gap-2 items-center border-b border-black/5 py-2" data-row="${i}">
                <input class="inv-field col-span-12 sm:col-span-6" data-k="name" placeholder="{{ $f['item'] }}">
                <input class="inv-field col-span-3 sm:col-span-2" data-k="qty" inputmode="decimal" dir="ltr" placeholder="{{ $f['qty'] }}">
                <input class="inv-field col-span-4 sm:col-span-2" data-k="price" inputmode="decimal" dir="ltr" placeholder="{{ $f['price'] }}">
                <span class="col-span-4 sm:col-span-1 text-end text-sm font-semibold" data-total dir="ltr">0</span>
                <button type="button" class="no-print col-span-1 text-red-500 text-lg" data-remove aria-label="{{ __('free_tools.ui.remove') }}"><i class="ri-close-line"></i></button>
            </div>`;
        }

        function render() {
            $('inv-items').innerHTML = items.map(rowHtml).join('');
            $('inv-items').querySelectorAll('[data-row]').forEach(row => {
                const item = items[row.dataset.row];
                row.querySelectorAll('[data-k]').forEach(input => {
                    input.value = item[input.dataset.k] ?? '';
                    input.addEventListener('input', () => { item[input.dataset.k] = input.value; update(); });
                });
                row.querySelector('[data-remove]').addEventListener('click', () => { items.splice(row.dataset.row, 1); if (!items.length) items.push({ qty: 1 }); render(); });
            });
            update();
        }

        function update() {
            const currency = $('inv-currency').value;
            let subtotal = 0;
            $('inv-items').querySelectorAll('[data-row]').forEach(row => {
                const item = items[row.dataset.row];
                const line = FT.parse(item.qty) * FT.parse(item.price);
                subtotal += line;
                row.querySelector('[data-total]').textContent = FT.number(line);
            });
            const discount = Math.min(FT.parse($('inv-discount').value), subtotal);
            const vat = (subtotal - discount) * FT.parse($('inv-vat').value) / 100;
            $('inv-subtotal').textContent = FT.number(subtotal) + ' ' + currency;
            $('inv-vat-amount').textContent = FT.number(vat) + ' ' + currency;
            $('inv-total').textContent = FT.number(subtotal - discount + vat) + ' ' + currency;
            save();
        }

        function save() {
            const data = { items };
            fields.forEach(id => data[id] = $(id).value);
            try { localStorage.setItem(KEY, JSON.stringify(data)); } catch (e) {}
        }

        function load() {
            let data = null;
            try { data = JSON.parse(localStorage.getItem(KEY)); } catch (e) {}
            if (data) fields.forEach(id => { if (data[id] !== undefined) $(id).value = data[id]; });
            items = data?.items?.length ? data.items : [{ qty: 1 }];
            if (!$('inv-number').value) $('inv-number').value = '0001';
            if (!$('inv-date').value) $('inv-date').value = new Date().toISOString().slice(0, 10);
            render();
        }

        fields.forEach(id => $(id).addEventListener('input', update));
        $('inv-add').addEventListener('click', () => { items.push({ qty: 1 }); render(); });
        $('inv-print').addEventListener('click', () => window.print());
        $('inv-new').addEventListener('click', () => {
            // keep the business details and VAT rate, start a new number
            const next = String((parseInt($('inv-number').value.replace(/\D/g, ''), 10) || 0) + 1).padStart(4, '0');
            ['inv-to'].forEach(id => $(id).value = '');
            $('inv-discount').value = '0';
            $('inv-number').value = next;
            $('inv-date').value = new Date().toISOString().slice(0, 10);
            items = [{ qty: 1 }];
            render();
        });
        load();
    });
</script>
