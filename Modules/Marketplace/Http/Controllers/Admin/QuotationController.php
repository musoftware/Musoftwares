<?php

namespace Modules\Marketplace\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Modules\Shortlink\Services\ShortlinkService;

class QuotationController extends Controller
{
    /**
     * Display a listing of quotations with KPIs and filters.
     */
    public function index(Request $request)
    {
        $query = Quotation::with(['currencyModel', 'creator', 'shortlink'])
            ->withCount(['items', 'orders', 'orders as paid_orders_count' => function ($q) {
                $q->where('status', 'paid');
            }]);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('quotation_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            $query->where('currency', $request->input('currency'));
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['created_at', 'title', 'quotation_number', 'development_total', 'grand_total', 'views_count', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $quotations = $query->paginate(15)->withQueryString();

        // Calculate KPI Metrics
        $totalQuotations = Quotation::count();
        $activeQuotations = Quotation::where('status', 'active')->count();
        $totalViews = Quotation::sum('views_count');
        $totalOrders = DB::table('quotation_orders')->where('status', 'paid')->count();
        $totalCollected = DB::table('quotation_orders')->where('status', 'paid')->sum('deposit_amount');

        $currencies = Currency::query()->orderBy('currency')->get(['id', 'currency', 'name', 'symbol']);

        return Inertia::render('Admin/Quotations/Index', [
            'quotations' => $quotations,
            'filters' => $request->only(['search', 'status', 'currency', 'sort', 'direction']),
            'metrics' => [
                'total_quotations' => $totalQuotations,
                'active_quotations' => $activeQuotations,
                'total_views' => $totalViews,
                'total_orders' => $totalOrders,
                'total_collected' => (float) $totalCollected,
            ],
            'currencies' => $currencies,
        ]);
    }

    /**
     * Show the form for creating a new quotation.
     */
    public function create()
    {
        $currencies = Currency::query()->orderBy('currency')->get(['id', 'currency', 'name', 'symbol']);
        $defaultCurrency = Currency::where('currency', 'USD')->first() ?? $currencies->first();

        $defaultMarkdown = "### 📌 نطاق العمل والمخرجات (Scope & Deliverables)\n- تصميم واجهات مستخدم احترافية بالكامل متجاوبة مع كافة الشاشات.\n- برمجة وتطوير لوحة تحكم إدارية متقدمة.\n- ربط بوابات الدفع الإلكتروني وتأمين المعاملات المالية.\n- اختبار شامل للأداء والحماية قبل الإطلاق.\n\n### ⏱️ الجدول الزمني للتنفيذ (Roadmap)\n- **المرحلة الأولى:** تصميم الواجهات وتجربة المستخدم (UI/UX).\n- **المرحلة الثانية:** التطوير البرمجي وقواعد البيانات.\n- **المرحلة الثالثة:** الاختبار النهائي والإطلاق الرسمي.\n\n### 📄 الشروط والأحكام (Terms & Conditions)\n- يتم سداد دفعة مقدمة 50% لبدء العمل فوراً.\n- باقي المبلغ 50% يستحق عند تسليم المشروع والاعتماد النهائي.\n- يشمل العرض فترة دعم فني وصيانة مجانية لمدة 30 يوماً بعد التسليم.";

        return Inertia::render('Admin/Quotations/Create', [
            'currencies' => $currencies,
            'defaultCurrencyId' => $defaultCurrency?->id,
            'defaultCurrencyCode' => $defaultCurrency?->currency ?? 'USD',
            'defaultMarkdown' => $defaultMarkdown,
        ]);
    }

    /**
     * Store a newly created quotation in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'currency' => 'required|string|max:10',
            'currency_id' => 'nullable|exists:currencies,id',
            'deposit_percentage' => 'required|numeric|min:1|max:100',
            'valid_until' => 'nullable|date',
            'status' => 'required|string|in:active,draft,archived',
            'scope_markdown' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:our_work,indicative_cost',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.external_link' => 'nullable|string|max:1000',
            'items.*.link_label' => 'nullable|string|max:255',
        ]);

        $quotation = DB::transaction(function () use ($validated, $request) {
            $currencyModel = null;
            if (!empty($validated['currency_id'])) {
                $currencyModel = Currency::find($validated['currency_id']);
            } elseif (!empty($validated['currency'])) {
                $currencyModel = Currency::where('currency', $validated['currency'])->first();
            }

            $quote = Quotation::create([
                'title' => $validated['title'],
                'created_by_user_id' => auth()->id(),
                'currency_id' => $currencyModel?->id,
                'currency' => $currencyModel?->currency ?? $validated['currency'],
                'deposit_percentage' => $validated['deposit_percentage'] ?? 50.00,
                'status' => $validated['status'] ?? 'active',
                'valid_until' => $validated['valid_until'] ?? null,
                'scope_markdown' => $validated['scope_markdown'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                QuotationItem::create([
                    'quotation_id' => $quote->id,
                    'type' => $itemData['type'],
                    'title' => $itemData['title'],
                    'description' => $itemData['description'] ?? null,
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'external_link' => $itemData['external_link'] ?? null,
                    'link_label' => $itemData['link_label'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            $quote->recalculateTotals();

            // Auto-generate shortlink
            try {
                $publicUrl = route('guest.quotations.show', ['uuid' => $quote->uuid]);
                $shortlinkService = app(ShortlinkService::class);
                $shortlink = $shortlinkService->create([
                    'destination_url' => $publicUrl,
                    'label' => "Quotation: {$quote->quotation_number} - {$quote->title}",
                    'title' => $quote->title,
                    'description' => "عرض سعر رسمي - مسوفتوير: {$quote->title}",
                    'created_by_user_id' => auth()->id(),
                    'expires_at' => $quote->valid_until,
                ], $quote);

                $quote->updateQuietly(['shortlink_id' => $shortlink->id]);
            } catch (\Throwable $e) {
                report($e);
            }

            return $quote;
        });

        return redirect()->route('admin.marketplace.quotations.show', $quotation->id)
            ->with('success', 'تم إنشاء عرض السعر وتوليد الرابط العام والمختصر بنجاح!');
    }

    /**
     * Display the specified quotation.
     */
    public function show(Quotation $quotation)
    {
        $quotation->load([
            'items',
            'creator',
            'currencyModel',
            'shortlink',
            'orders.user',
            'orders.invoice',
        ]);

        $publicUrl = route('guest.quotations.show', ['uuid' => $quotation->uuid]);
        $shortUrl = $quotation->shortlink ? route('shortlink.redirect', ['code' => $quotation->shortlink->short_code]) : $publicUrl;

        // Prepare WhatsApp message
        $shareUrl = $shortUrl ?: $publicUrl;
        $waText = "مرحباً بك،\nيسعدنا تقديم عرض السعر الخاص بمشروعكم:\n📌 *{$quotation->title}*\n"
            . "💰 إجمالي قيمة العمل والتطوير: *{$quotation->development_total} {$quotation->currency}*\n"
            . "💳 الدفعة المقدمة لبدء العمل ({$quotation->deposit_percentage}%): *{$quotation->deposit_amount} {$quotation->currency}*\n\n"
            . "🔗 للاطلاع على تفاصيل العرض والبنود والدفع الآمن:\n{$shareUrl}";

        $whatsappShareUrl = "https://api.whatsapp.com/send?text=" . urlencode($waText);

        return Inertia::render('Admin/Quotations/Show', [
            'quotation' => $quotation,
            'publicUrl' => $publicUrl,
            'shortUrl' => $shortUrl,
            'whatsappShareUrl' => $whatsappShareUrl,
            'whatsappMessage' => $waText,
        ]);
    }

    /**
     * Show the form for editing the specified quotation.
     */
    public function edit(Quotation $quotation)
    {
        $quotation->load(['items', 'currencyModel']);
        $currencies = Currency::query()->orderBy('currency')->get(['id', 'currency', 'name', 'symbol']);

        return Inertia::render('Admin/Quotations/Edit', [
            'quotation' => $quotation,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Update the specified quotation in storage.
     */
    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'currency' => 'required|string|max:10',
            'currency_id' => 'nullable|exists:currencies,id',
            'deposit_percentage' => 'required|numeric|min:1|max:100',
            'valid_until' => 'nullable|date',
            'status' => 'required|string|in:active,draft,archived',
            'scope_markdown' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:our_work,indicative_cost',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.external_link' => 'nullable|string|max:1000',
            'items.*.link_label' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $quotation) {
            $currencyModel = null;
            if (!empty($validated['currency_id'])) {
                $currencyModel = Currency::find($validated['currency_id']);
            } elseif (!empty($validated['currency'])) {
                $currencyModel = Currency::where('currency', $validated['currency'])->first();
            }

            $quotation->update([
                'title' => $validated['title'],
                'currency_id' => $currencyModel?->id,
                'currency' => $currencyModel?->currency ?? $validated['currency'],
                'deposit_percentage' => $validated['deposit_percentage'] ?? 50.00,
                'status' => $validated['status'] ?? 'active',
                'valid_until' => $validated['valid_until'] ?? null,
                'scope_markdown' => $validated['scope_markdown'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sync items
            $quotation->items()->delete();
            foreach ($validated['items'] as $index => $itemData) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'type' => $itemData['type'],
                    'title' => $itemData['title'],
                    'description' => $itemData['description'] ?? null,
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'external_link' => $itemData['external_link'] ?? null,
                    'link_label' => $itemData['link_label'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            $quotation->recalculateTotals();
        });

        return redirect()->route('admin.marketplace.quotations.show', $quotation->id)
            ->with('success', 'تم تحديث عرض السعر بنجاح!');
    }

    /**
     * Remove the specified quotation from storage.
     */
    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('admin.marketplace.quotations.index')
            ->with('success', 'تم حذف عرض السعر بنجاح.');
    }

    /**
     * Duplicate an existing quotation into a new draft.
     */
    public function duplicate(Quotation $quotation)
    {
        $quotation->load('items');

        $newQuote = DB::transaction(function () use ($quotation) {
            $clone = $quotation->replicate([
                'uuid',
                'quotation_number',
                'shortlink_id',
                'views_count',
                'last_viewed_at',
            ]);

            $clone->title = "نسخة من: " . $quotation->title;
            $clone->status = 'draft';
            $clone->created_by_user_id = auth()->id();
            $clone->save();

            foreach ($quotation->items as $item) {
                $itemClone = $item->replicate(['quotation_id']);
                $itemClone->quotation_id = $clone->id;
                $itemClone->save();
            }

            $clone->recalculateTotals();

            return $clone;
        });

        return redirect()->route('admin.marketplace.quotations.edit', $newQuote->id)
            ->with('success', "تم استنساخ العرض بنجاح ورقم العرض الجديد هو {$newQuote->quotation_number}");
    }
}
