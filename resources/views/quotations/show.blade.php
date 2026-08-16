<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Quotation {{ $quote['code'] ?? 'QT-PROPOSAL' }} - Musoftware</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', 'Cairo', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            line-height: 1.5;
            padding: 30px 15px 80px;
        }
        .page-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            padding: 48px;
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 28px;
            margin-bottom: 32px;
        }
        .brand {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .logo-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-badge {
            width: 38px;
            height: 38px;
            background: #0f172a;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 20px;
        }
        .brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
        }
        .brand-subtitle {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0284c7;
        }
        .company-info {
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
            margin-top: 4px;
        }
        .quote-meta {
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .quote-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            align-self: flex-end;
        }
        .quote-code {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .meta-dates {
            font-size: 12px;
            color: #64748b;
        }
        .meta-dates span {
            font-weight: 600;
            color: #334155;
        }
        
        .client-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 32px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .client-col h4 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .client-col p {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .client-col span {
            font-size: 12px;
            color: #64748b;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0f172a;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        table.quotation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        table.quotation-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            text-align: left;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        table.quotation-table td {
            padding: 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #1e293b;
        }
        table.quotation-table td.desc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        table.quotation-table td.price {
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }
        table.quotation-table tr.category-header td {
            background: #f8fafc;
            font-weight: 700;
            font-size: 12px;
            color: #0284c7;
            padding: 8px 14px;
        }

        .summary-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 36px;
        }
        .summary-box {
            width: 320px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
        }
        .summary-row.discount {
            color: #059669;
            font-weight: 600;
        }
        .summary-row.total {
            border-top: 2px solid #cbd5e1;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .terms-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            border-top: 1px solid #f1f5f9;
            padding-top: 24px;
            margin-bottom: 32px;
        }
        .term-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
        }
        .term-card h5 {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .term-card p {
            font-size: 11px;
            color: #64748b;
            line-height: 1.5;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 2px solid #f1f5f9;
            padding-top: 28px;
            margin-top: 20px;
        }
        .sign-col {
            width: 220px;
            text-align: center;
        }
        .sign-line {
            height: 1px;
            background: #cbd5e1;
            margin-bottom: 8px;
        }
        .sign-col p.name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .sign-col p.title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
        }
        .seal-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #0284c7;
            border-radius: 12px;
            padding: 10px 16px;
            color: #0284c7;
            background: #f0f9ff;
        }
        .seal-badge span.seal-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .seal-badge span.seal-num {
            font-size: 9px;
            font-weight: 600;
            color: #0369a1;
        }

        /* Floating Action Bar (Interactive, hidden on print) */
        .action-bar {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: #0f172a;
            border: 1px solid #334155;
            padding: 10px 18px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }
        .action-btn {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .action-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .action-btn.whatsapp {
            background: #25D366;
            color: #0f172a;
        }
        .action-btn.whatsapp:hover {
            background: #20bd5a;
        }
        .action-btn.secondary {
            background: #1e293b;
            color: #cbd5e1;
            border: 1px solid #475569;
        }
        .action-btn.secondary:hover {
            background: #334155;
            color: #ffffff;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .page-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Hidden during print) -->
    <div class="action-bar no-print">
        <button onclick="window.print()" class="action-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print / Save as PDF
        </button>

        @if(!empty($whatsappUrl))
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="action-btn whatsapp">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                Discuss on WhatsApp
            </a>
        @endif

        <a href="{{ route('estimator') }}" class="action-btn secondary">
            &larr; Edit in Estimator
        </a>
    </div>

    <div class="page-container">
        <!-- Header / Corporate Letterhead -->
        <header class="header">
            <div class="brand">
                <div class="logo-title">
                    <div class="logo-badge">M</div>
                    <div>
                        <div class="brand-name">MUSOFTWARE</div>
                        <div class="brand-subtitle">Software Engineering &amp; SaaS Systems</div>
                    </div>
                </div>
                <div class="company-info">
                    Musoftware Solutions LLC &bull; Suez / Cairo, Egypt<br>
                    Commercial Registration: CR-481920 &bull; info@musoftwares.com<br>
                    Direct Engineering Hotline / WhatsApp: +20 101 521 8548
                </div>
            </div>

            <div class="quote-meta">
                <span class="quote-badge">Official Quotation</span>
                <div class="quote-code">{{ $quote['code'] ?? 'QT-' . date('Ymd-His') }}</div>
                <div class="meta-dates">
                    Issue Date: <span>{{ $quote['cairo_date'] ?? now()->timezone('Africa/Cairo')->format('M d, Y') }}</span><br>
                    Valid Until: <span>{{ $quote['valid_until'] ?? now()->timezone('Africa/Cairo')->addDays(30)->format('M d, Y') }}</span>
                </div>
            </div>
        </header>

        <!-- Client & Scope Meta -->
        <section class="client-section">
            <div class="client-col">
                <h4>Prepared For:</h4>
                <p>{{ $quote['client_name'] ?? 'Valued Business Partner / Client' }}</p>
                <span>{{ $quote['client_business'] ?? 'Custom Software & Infrastructure Scope' }}</span>
                @if(!empty($quote['client_mobile']))
                    <br><span>Mobile / WhatsApp: {{ $quote['client_mobile'] }}</span>
                @endif
            </div>
            <div class="client-col" style="text-align: right;">
                <h4>Project Scope / Platforms:</h4>
                <p>{{ $quote['platforms_summary'] ?? 'Integrated Multi-Platform Solution' }}</p>
                <span>Currency: <strong>{{ ($quote['is_usd'] ?? true) ? 'USD ($)' : 'EGP (Egyptian Pound)' }}</strong></span>
            </div>
        </section>

        <!-- Itemized Engineering Deliverables Table -->
        <div class="section-title">Itemized Project Breakdown &amp; Deliverables</div>
        <table class="quotation-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Deliverable / Engineering Component</th>
                    <th style="width: 25%; text-align: center;">Platform / Unit</th>
                    <th style="width: 25%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- Platform Screens -->
                <tr class="category-header">
                    <td colspan="3">1. Core Platform Architecture &amp; Interfaces</td>
                </tr>
                @foreach($quote['platform_items'] ?? [] as $platform)
                    <tr>
                        <td>
                            <strong>{{ $platform['title'] }}</strong>
                            <div class="desc">{{ $platform['count'] }} {{ $platform['unit'] }}s @ ${{ $platform['rate'] }}/{{ strtolower($platform['unit']) }}</div>
                        </td>
                        <td style="text-align: center;">{{ $platform['count'] }} {{ $platform['unit'] }}s</td>
                        <td class="price">{{ $quote['formatter']($platform['cost']) }}</td>
                    </tr>
                @endforeach

                <!-- Addons & Modules -->
                @if(!empty($quote['itemized_addons']))
                    <tr class="category-header">
                        <td colspan="3">2. Specialized Modules, Integrations &amp; Automation</td>
                    </tr>
                    @foreach($quote['itemized_addons'] as $addon)
                        <tr>
                            <td>
                                <strong>{{ $addon['title'] }}</strong>
                                @if(!empty($addon['desc']))
                                    <div class="desc">{{ $addon['desc'] }}</div>
                                @endif
                            </td>
                            <td style="text-align: center;">Module / Service</td>
                            <td class="price">{{ $quote['formatter']($addon['cost']) }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <!-- Financial Summary -->
        <div class="summary-wrapper">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>{{ $quote['formatter']($quote['subtotal_usd']) }}</span>
                </div>

                @if(!empty($quote['is_bundle_discount']) && $quote['discount_usd'] > 0)
                    <div class="summary-row discount">
                        <span>10% Ecosystem Bundle Discount:</span>
                        <span>-{{ $quote['formatter']($quote['discount_usd']) }}</span>
                    </div>
                @endif

                <div class="summary-row total">
                    <span>Estimated Total:</span>
                    <span>{{ $quote['formatter']($quote['total_usd']) }}</span>
                </div>

                @if($quote['is_usd'])
                    <div style="font-size: 10px; color: #94a3b8; text-align: right;">
                        Equivalent: ~{{ number_format($quote['total_egp']) }} EGP (@ {{ $quote['exchange_rate'] }} EGP/USD)
                    </div>
                @else
                    <div style="font-size: 10px; color: #94a3b8; text-align: right;">
                        USD Base: ${{ number_format($quote['total_usd']) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Terms, Guarantees & Warranty -->
        <div class="section-title">Terms of Service &amp; Engineering Warranty</div>
        <div class="terms-grid">
            <div class="term-card">
                <h5>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    3-Month Free Warranty
                </h5>
                <p>Complete post-launch technical support and bug fixing warranty for 90 days with zero additional charges.</p>
            </div>

            <div class="term-card">
                <h5>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    100% Full Source Code Ownership
                </h5>
                <p>Full intellectual property, databases, and Git repositories handed over unconditionally upon project completion.</p>
            </div>

            <div class="term-card">
                <h5>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    Milestone-Based Delivery
                </h5>
                <p>Payment structure tied to transparent sprint milestones (Architecture, Prototype, Beta Review, Final Deployment).</p>
            </div>

            <div class="term-card">
                <h5>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Direct Senior Engineering Contact
                </h5>
                <p>Continuous communication with dedicated technical leads via WhatsApp and private staging environments.</p>
            </div>
        </div>

        <!-- Official Signatures & Digital Seal -->
        <footer class="signatures">
            <div class="sign-col">
                <div class="sign-line"></div>
                <p class="name">Mahmoud M.</p>
                <p class="title">Lead Principal Architect &bull; Musoftware</p>
            </div>

            <div class="seal-badge">
                <span class="seal-title">&check; Officially Issued</span>
                <span class="seal-num">REF: {{ $quote['code'] ?? 'QT-VERIFIED' }}</span>
            </div>

            <div class="sign-col">
                <div class="sign-line"></div>
                <p class="name">{{ $quote['client_name'] ?? 'Client / Authorized Signature' }}</p>
                <p class="title">Approval &amp; Acceptance</p>
            </div>
        </footer>
    </div>

</body>
</html>
