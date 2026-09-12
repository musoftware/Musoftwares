<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Quotation {{ $quote['code'] ?? 'QT-PROPOSAL' }} - Musoftware</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-base: #f8fafc;
            --surface: #ffffff;
            --text-main: #090d16;
            --text-muted: #4b5563;
            --text-subtle: #8a99ad;
            --border-light: #e4e9f0;
            --border-dark: #090d16;
            --accent-brand: #0284c7;
            --accent-navy: #0f172a;
            --stamp-blue: #1e3a8a;
        }

        body {
            font-family: 'Inter', 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            line-height: 1.6;
            padding: 40px 20px 120px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Container Document */
        .proposal-document {
            position: relative;
            z-index: 10;
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 50px;
        }

        /* Individual Page Sheets (A4 proportions on screen) */
        .page-sheet {
            background: var(--surface);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.03);
            padding: 64px 60px;
            position: relative;
            overflow: hidden;
            min-height: 1100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Internal Sheet Abstract Geometric Backgrounds */
        .sheet-bg-graphics {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            opacity: 0.85;
        }

        .sheet-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* ── SECTION HEADERS & TITLES ── */
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            color: var(--accent-brand);
            margin-bottom: 12px;
        }

        .section-tag::before {
            content: "";
            width: 20px;
            height: 2px;
            background: var(--accent-brand);
        }

        .section-heading {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.8px;
            color: #090d16;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        /* ── PAGE 1: COVER & INTRO ── */
        .cover-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 24px;
        }

        .brand-cluster {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .brand-monogram {
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            box-shadow: none;
            flex-shrink: 0;
        }

        .brand-monogram img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            display: block;
        }

        .brand-text h3 {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #090d16;
            line-height: 1.15;
        }

        .brand-text span {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: var(--accent-brand);
            display: block;
            margin-top: 2px;
        }

        .cover-ref-badge {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--text-muted);
        }

        .cover-ref-badge strong {
            display: block;
            font-size: 14px;
            color: #090d16;
        }

        /* Huge Whitespace & Intro Hero */
        .cover-body {
            margin: auto 0;
            padding: 80px 0 60px;
        }

        .cover-category {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent-brand);
            margin-bottom: 20px;
        }

        .cover-category::before {
            content: "";
            width: 24px;
            height: 2px;
            background: var(--accent-brand);
        }

        .cover-title {
            font-size: 44px;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -1.5px;
            color: #090d16;
            margin-bottom: 24px;
            max-width: 680px;
        }

        .cover-subtitle {
            font-size: 18px;
            font-weight: 400;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 640px;
            margin-bottom: 48px;
        }

        /* 4-Column Minimalist Spec Shelf */
        .cover-spec-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            background: #fafbfc;
            border: 1px solid var(--border-light);
            border-radius: 14px;
            padding: 20px 0;
            margin-top: 12px;
        }

        .spec-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 0 22px;
            border-right: 1px solid #edf2f7;
        }

        .spec-item:last-child {
            border-right: none;
        }

        .spec-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.1px;
            color: var(--text-subtle);
        }

        .spec-value {
            font-size: 13.5px;
            font-weight: 800;
            color: #090d16;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .spec-meta {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* Cover Footer with Stamp Impression */
        .cover-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 30px;
            border-top: 1px solid var(--border-light);
        }

        .cover-footer-info {
            font-size: 11px;
            color: var(--text-subtle);
            line-height: 1.7;
        }

        /* ── OFFICIAL EXECUTIVE VECTOR STAMP ── */
        .stamp-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            transform: rotate(-7deg);
            opacity: 0.92;
            transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            user-select: none;
        }

        .stamp-wrapper:hover {
            transform: rotate(-3deg) scale(1.03);
        }

        .stamp-svg {
            width: 100%;
            height: 100%;
        }

        /* ── PAGE 2: ARCHITECTURAL NARRATIVE & BLUEPRINT ── */
        .narrative-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 14px;
            padding: 28px 32px;
            margin-bottom: 28px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .narrative-card h4 {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #090d16;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .narrative-text {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.8;
        }

        /* Tech Stack Chips */
        .tech-chips-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .tech-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .tech-chip::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-brand);
        }

        /* Phased Implementation Roadmap */
        .roadmap-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 16px;
        }

        .phase-card {
            background: #fafbfc;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .phase-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .phase-tag {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent-brand);
        }

        .phase-duration {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-subtle);
        }

        .phase-title {
            font-size: 13.5px;
            font-weight: 800;
            color: #090d16;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .phase-deliverables {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ── PAGE 3: DETAILED ITEMIZATION & FINANCIALS ── */
        .inner-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 1.5px solid #090d16;
            padding-bottom: 18px;
            margin-bottom: 32px;
        }

        .inner-header-title h2 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.6px;
            color: #090d16;
        }

        .inner-header-title span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .inner-quote-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 700;
            color: #090d16;
        }

        /* Executive Table Styling */
        .table-container {
            width: 100%;
            margin-bottom: 32px;
        }

        table.executive-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        table.executive-table th {
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-subtle);
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            background: #fafbfc;
        }

        table.executive-table th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        table.executive-table th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            text-align: right;
        }

        table.executive-table th.center,
        table.executive-table td.center {
            text-align: center;
        }

        table.executive-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f4f8;
            vertical-align: middle;
            color: var(--text-main);
        }

        table.executive-table tr:hover td {
            background: #fafbfc;
        }

        .item-main {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .item-name {
            font-weight: 700;
            font-size: 13.5px;
            color: #090d16;
        }

        .item-desc {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .unit-tag {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 6px;
        }

        .price-col {
            text-align: right;
            font-family: 'Inter', -apple-system, sans-serif;
            font-feature-settings: "tnum" 1;
            font-weight: 700;
            font-size: 13.5px;
            color: #090d16;
            white-space: nowrap;
        }

        .category-row td {
            background: #f8fafc !important;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--accent-brand);
            padding: 10px 16px;
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
        }

        /* ── REFINED INTERNATIONAL FINANCIAL LEDGER (المواصفات العالمية) ── */
        .ledger-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 36px;
        }

        .ledger-box {
            width: 400px;
            background: #fafbfc;
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }

        .ledger-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 10px;
            margin-bottom: 2px;
        }

        .ledger-header span.title {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-subtle);
        }

        .ledger-header span.badge {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #eff6ff;
            color: var(--accent-brand);
            border: 1px solid #bfdbfe;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .ledger-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-muted);
        }

        .ledger-row .val {
            font-family: 'Inter', -apple-system, sans-serif;
            font-feature-settings: "tnum" 1;
            font-weight: 700;
            font-size: 14px;
            color: #090d16;
        }

        .ledger-row.discount {
            color: #059669;
        }

        .ledger-row.discount .val {
            color: #059669;
            font-weight: 800;
        }

        .ledger-row.grand-total {
            border-top: 1.5px solid #090d16;
            padding-top: 14px;
            margin-top: 6px;
            font-size: 15px;
            font-weight: 900;
            color: #090d16;
        }

        .ledger-row.grand-total .val {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #090d16;
        }

        .equivalent-note {
            font-size: 11px;
            color: var(--text-subtle);
            text-align: right;
            margin-top: 2px;
            line-height: 1.4;
        }

        /* Milestone Payment Schedule (International SOW Standard) */
        .milestone-schedule-box {
            margin-top: 10px;
            padding-top: 14px;
            border-top: 1px dashed #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .milestone-schedule-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-subtle);
            margin-bottom: 2px;
        }

        .milestone-schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .milestone-schedule-item span.amt {
            font-weight: 700;
            color: #090d16;
            font-feature-settings: "tnum" 1;
        }

        /* Signatures Section */
        .signatures-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 24px;
            border-top: 1px solid var(--border-light);
            margin-top: auto;
        }

        .sign-block {
            width: 240px;
            text-align: center;
        }

        .sign-rule {
            height: 1px;
            background: #cbd5e1;
            margin-bottom: 8px;
        }

        .sign-block .signer-name {
            font-size: 13.5px;
            font-weight: 800;
            color: #090d16;
        }

        .sign-block .signer-title {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-subtle);
        }

        /* Floating Web Control Bar */
        .floating-action-bar {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.4);
            z-index: 9999;
            backdrop-filter: blur(8px);
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .action-link:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

        .action-link.primary {
            background: #0284c7;
            border-color: #0284c7;
        }

        .action-link.primary:hover {
            background: #0369a1;
        }

        .action-link.whatsapp {
            background: #22c55e;
            border-color: #22c55e;
            color: #090d16;
        }

        .action-link.whatsapp:hover {
            background: #16a34a;
        }

        .action-text-short {
            display: none;
        }

        .action-text-long {
            display: inline;
        }

        /* ── RESPONSIVE MOBILE STYLES (Apple HIG & International Standards) ── */
        @media screen and (max-width: 768px) {
            body {
                padding: 16px 12px 110px;
            }

            .proposal-document {
                gap: 28px;
            }

            .page-sheet {
                padding: 28px 18px;
                min-height: auto;
                border-radius: 16px;
            }

            .cover-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding-bottom: 20px;
            }

            .cover-ref-badge {
                text-align: left;
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8fafc;
                padding: 8px 12px;
                border-radius: 8px;
                border: 1px solid var(--border-light);
            }

            .cover-title {
                font-size: 28px;
                letter-spacing: -0.8px;
                line-height: 1.2;
                margin-bottom: 16px;
            }

            .cover-subtitle {
                font-size: 14.5px;
                margin-bottom: 24px;
                line-height: 1.5;
            }

            .cover-spec-grid {
                grid-template-columns: repeat(2, 1fr);
                padding: 16px 14px;
                gap: 16px;
            }

            .spec-item {
                padding: 0;
                border-right: none;
            }

            .spec-item:nth-child(odd) {
                border-right: 1px solid #edf2f7;
                padding-right: 12px;
            }

            .spec-item:nth-child(even) {
                padding-left: 12px;
            }

            .spec-value {
                font-size: 13px;
                white-space: normal;
                word-break: break-word;
            }

            .cover-footer {
                flex-direction: column-reverse;
                align-items: flex-start;
                gap: 24px;
                margin-top: 32px;
            }

            .official-stamp-container {
                align-self: center;
            }

            .roadmap-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 20px;
                border: 1px solid var(--border-light);
                border-radius: 10px;
            }

            table.executive-table {
                min-width: 480px;
            }

            .inner-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .inner-header-title h2 {
                font-size: 18px;
                line-height: 1.3;
            }

            .ledger-wrapper {
                justify-content: stretch;
            }

            .ledger-box {
                width: 100%;
                min-width: 0;
            }

            .signatures-grid {
                flex-direction: column;
                align-items: stretch;
                gap: 24px;
                margin-top: 32px;
            }

            .sign-block {
                width: 100%;
                text-align: center;
            }

            /* Optimized Mobile Floating Bar */
            .floating-action-bar {
                bottom: 12px;
                width: calc(100% - 20px);
                max-width: 440px;
                padding: 6px 8px;
                gap: 6px;
                justify-content: space-between;
            }

            .action-link {
                padding: 8px 10px;
                font-size: 11px;
                gap: 5px;
                flex: 1;
                justify-content: center;
                white-space: nowrap;
            }

            .action-link svg {
                width: 13px;
                height: 13px;
            }

            .action-text-long {
                display: none;
            }

            .action-text-short {
                display: inline;
            }
        }

        @media screen and (max-width: 480px) {
            .cover-spec-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .spec-item,
            .spec-item:nth-child(odd),
            .spec-item:nth-child(even) {
                padding: 0 0 10px 0;
                border-right: none;
                border-bottom: 1px solid #edf2f7;
            }

            .spec-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            .action-link {
                padding: 8px 6px;
                font-size: 10.5px;
            }
        }

        /* ── PRINT & PDF STYLES ── */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000000;
            }

            .floating-action-bar,
            .no-print {
                display: none !important;
            }

            .proposal-document {
                max-width: 100% !important;
                margin: 0 !important;
                gap: 0 !important;
            }

            .page-sheet {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 30mm 20mm !important;
                min-height: 100vh !important;
                page-break-after: always;
                break-after: page;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Controller Bar (Hidden when printed or exported to PDF) -->
    <nav class="floating-action-bar no-print" aria-label="Proposal Actions">
        <button onclick="window.print()" class="action-link primary" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span class="action-text-long">Print / Save as PDF</span>
            <span class="action-text-short">Print</span>
        </button>

        @if(!empty($quote['code']))
            <a href="{{ route('public.quotation.pdf', ['code' => $quote['code']]) }}" class="action-link" target="_blank">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span class="action-text-long">Direct PDF Download</span>
                <span class="action-text-short">PDF</span>
            </a>
        @endif

        @if(!empty($whatsappUrl))
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="action-link whatsapp">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                <span class="action-text-long">Discuss Scope on WhatsApp</span>
                <span class="action-text-short">WhatsApp</span>
            </a>
        @endif

        <a href="{{ route('estimator') }}" class="action-link">
            <span>&larr;</span>
            <span class="action-text-long">Estimator</span>
            <span class="action-text-short">Back</span>
        </a>
    </nav>

    <!-- Main Proposal Document (3 Multi-Page Executive Sheets) -->
    <main class="proposal-document">

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SHEET 1: EXECUTIVE COVER & INTRODUCTION                          -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <section class="page-sheet" id="cover-page">
            <!-- Background Abstract Geometric Rings & Shapes -->
            <div class="sheet-bg-graphics" aria-hidden="true">
                <svg width="100%" height="100%" viewBox="0 0 900 1100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="820" cy="120" r="280" stroke="#0f172a" stroke-width="1" stroke-dasharray="4 6" opacity="0.04" />
                    <circle cx="820" cy="120" r="220" stroke="#0284c7" stroke-width="1.5" opacity="0.06" />
                    <circle cx="820" cy="120" r="160" stroke="#0f172a" stroke-width="1" opacity="0.04" />
                    <circle cx="820" cy="120" r="100" stroke="#0284c7" stroke-width="1" stroke-dasharray="2 4" opacity="0.08" />

                    <path d="M-80 920 C 120 860, 240 1020, 480 960 C 620 920, 780 1040, 980 980" stroke="#0284c7" stroke-width="1.5" opacity="0.05" fill="none" />
                    <path d="M-60 960 C 140 900, 260 1060, 500 1000 C 640 960, 800 1080, 1000 1020" stroke="#0f172a" stroke-width="1" stroke-dasharray="6 8" opacity="0.04" fill="none" />

                    <g opacity="0.07" stroke="#090d16" stroke-width="1">
                        <line x1="60" y1="40" x2="80" y2="40" />
                        <line x1="70" y1="30" x2="70" y2="50" />
                        <line x1="830" y1="1040" x2="850" y2="1040" />
                        <line x1="840" y1="1030" x2="840" y2="1050" />
                    </g>
                </svg>
            </div>

            <div class="sheet-content">
                <!-- Cover Header -->
                <header class="cover-header">
                    <div class="brand-cluster">
                        <div class="brand-monogram">
                            <img src="{{ asset('favicon.svg') }}" alt="Musoftware Logo" width="58" height="58">
                        </div>
                        <div class="brand-text">
                            <h3>MUSOFTWARE</h3>
                            <span>Engineering &amp; Enterprise Architecture</span>
                        </div>
                    </div>

                    <div class="cover-ref-badge">
                        <span>OFFICIAL PROPOSAL</span>
                        <strong>{{ $quote['code'] ?? 'QT-' . date('Ymd-His') }}</strong>
                    </div>
                </header>

                <!-- Cover Hero & Whitespace -->
                <div class="cover-body">
                    <div class="cover-category">Architectural Specification &amp; Investment Scope</div>
                    
                    <h1 class="cover-title">
                        {{ $quote['platforms_summary'] ?? 'Custom Software Infrastructure & Digital Ecosystem' }}
                    </h1>

                    <p class="cover-subtitle">
                        Comprehensive technical specification, platform screen itemization, integrations scope, and commercial investment schedule for high-availability enterprise delivery.
                    </p>

                    <!-- 4-Column Minimalist Metadata Shelf -->
                    <div class="cover-spec-grid">
                        <div class="spec-item">
                            <span class="spec-label">Target Organization</span>
                            <span class="spec-value">{{ $quote['client_business'] ?? 'Confidential Enterprise' }}</span>
                            <span class="spec-meta">Verified Enterprise Scope</span>
                        </div>

                        <div class="spec-item">
                            <span class="spec-label">Issue Date</span>
                            <span class="spec-value">{{ trim(str_replace(['(Cairo Time)', ' - '], ['', ' • '], $quote['cairo_date'] ?? now()->timezone('Africa/Cairo')->format('M d, Y • h:i A'))) }}</span>
                            <span class="spec-meta">Cairo Time (Africa/Cairo)</span>
                        </div>

                        <div class="spec-item">
                            <span class="spec-label">Valid Until</span>
                            <span class="spec-value">{{ $quote['valid_until'] ?? now()->timezone('Africa/Cairo')->addDays(30)->format('M d, Y') }}</span>
                            <span class="spec-meta">30-Day Rate Guarantee</span>
                        </div>

                        <div class="spec-item">
                            <span class="spec-label">Currency Standard</span>
                            <span class="spec-value">{{ ($quote['is_usd'] ?? true) ? 'USD ($)' : 'EGP (Egyptian Pound)' }}</span>
                            <span class="spec-meta">Base 1 USD = {{ $quote['exchange_rate'] ?? 50 }} EGP</span>
                        </div>
                    </div>
                </div>

                <!-- Cover Footer With Official Stamp Impression -->
                <footer class="cover-footer">
                    <div class="cover-footer-info">
                        Musoftware Engineering Group &bull; Suez, Egypt<br>
                        Confidential Architectural Document &bull; All Rights Reserved &copy; {{ date('Y') }}
                    </div>

                    <!-- Authentic Vector Corporate Stamp With Embedded Logo -->
                    <div class="stamp-wrapper" title="Officially Verified by Musoftware Principal Architecture">
                        <svg class="stamp-svg" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Outer Dashed Border -->
                            <circle cx="100" cy="100" r="94" stroke="#1e3a8a" stroke-width="2.5" stroke-dasharray="5 4" opacity="0.7" />
                            <!-- Inner Solid Border -->
                            <circle cx="100" cy="100" r="86" stroke="#1e3a8a" stroke-width="2.5" opacity="0.9" />
                            <!-- Center Crest Circle -->
                            <circle cx="100" cy="100" r="54" stroke="#1e3a8a" stroke-width="1.5" stroke-dasharray="2 3" opacity="0.6" />

                            <!-- Curved Text (SVG Path) -->
                            <path id="stampUpperCircle" d="M 24,100 A 76,76 0 0,1 176,100" fill="none" />
                            <text font-family="'Inter', sans-serif" font-size="10.5" font-weight="900" fill="#1e3a8a" letter-spacing="2.2">
                                <textPath href="#stampUpperCircle" startOffset="50%" text-anchor="middle">
                                    MUSOFTWARE ARCHITECTURE
                                </textPath>
                            </text>

                            <path id="stampLowerCircle" d="M 176,100 A 76,76 0 0,1 24,100" fill="none" />
                            <text font-family="'Inter', sans-serif" font-size="9.5" font-weight="800" fill="#1e3a8a" letter-spacing="2">
                                <textPath href="#stampLowerCircle" startOffset="50%" text-anchor="middle">
                                    VERIFIED &amp; APPROVED
                                </textPath>
                            </text>

                            <!-- Stamp Center Core With Musoftware Emblem -->
                            <g transform="translate(100, 96)">
                                <g transform="translate(0, -18) scale(0.12) translate(-153.5, -153.5)">
                                    <path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z" fill="#1e3a8a" />
                                </g>
                                <text y="14" font-family="'JetBrains Mono', monospace" font-size="9.5" font-weight="900" fill="#1e3a8a" text-anchor="middle" letter-spacing="1">
                                    OFFICIAL
                                </text>
                                <text y="24" font-family="'JetBrains Mono', monospace" font-size="7.5" font-weight="700" fill="#1e3a8a" text-anchor="middle" opacity="0.85">
                                    2026-SEAL
                                </text>
                            </g>
                        </svg>
                    </div>
                </footer>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SHEET 2: STRATEGIC BLUEPRINT, ARCHITECTURE & PHASED ROADMAP       -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <section class="page-sheet" id="architecture-page">
            <div class="sheet-bg-graphics" aria-hidden="true">
                <svg width="100%" height="100%" viewBox="0 0 900 1100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="850" cy="900" r="300" stroke="#0f172a" stroke-width="1" stroke-dasharray="3 6" opacity="0.03" />
                    <circle cx="850" cy="900" r="220" stroke="#0284c7" stroke-width="1" opacity="0.04" />
                </svg>
            </div>

            <div class="sheet-content">
                <!-- Sheet Header -->
                <header class="inner-header">
                    <div class="inner-header-title">
                        <h2>Section 01: Strategic Context &amp; Architectural Blueprint</h2>
                        <span>Technical methodology, engineering standards &bull; Delivery governance</span>
                    </div>

                    <div class="inner-quote-id">
                        REF: {{ $quote['code'] ?? 'QT-VERIFIED' }}
                    </div>
                </header>

                <!-- 1. Executive Summary -->
                <div class="narrative-card">
                    <h4>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        1. Executive Summary &amp; Business Objectives
                    </h4>
                    <p class="narrative-text">
                        {{ $quote['executive_summary'] ?? 'This proposal establishes the technical architecture, execution roadmap, and commercial investment schedule for the digital ecosystem. The system is architected for enterprise stability, rapid response times, and multi-channel synchronization, ensuring high business continuity and full intellectual property ownership.' }}
                    </p>
                </div>

                <!-- 2. Architectural Approach & Technology Stack -->
                <div class="narrative-card">
                    <h4>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        2. System Architecture &amp; Technology Stack
                    </h4>
                    <p class="narrative-text">
                        {{ $quote['architectural_approach'] ?? 'The system is constructed using a decoupled domain-driven architecture. User-facing interfaces communicate with an event-driven application layer with strict transactional persistence. All third-party gateways and asynchronous tasks are isolated behind resilient retry queues with automated telemetry.' }}
                    </p>

                    <div class="tech-chips-grid">
                        @foreach($quote['tech_stack'] ?? ['Laravel 12 (Core API Engine)', 'React 18 & Inertia.js', 'Tailwind CSS v4', 'PostgreSQL / SQLite Engine', 'REST & WebSocket Bridge', 'Docker Containerization'] as $tech)
                            <span class="tech-chip">{{ $tech }}</span>
                        @endforeach
                    </div>
                </div>

                <!-- 3. Phased Implementation Roadmap -->
                <div style="margin-top: 8px;">
                    <div class="section-tag">Governance Roadmap</div>
                    <div style="font-size: 16px; font-weight: 800; color: #090d16; margin-bottom: 14px;">Phased Implementation Sprints</div>

                    <div class="roadmap-container">
                        @foreach($quote['milestones'] ?? [] as $milestone)
                            <div class="phase-card">
                                <div>
                                    <div class="phase-meta">
                                        <span class="phase-tag">{{ $milestone['phase'] ?? 'Sprint' }}</span>
                                        <span class="phase-duration">{{ $milestone['duration'] ?? 'Scheduled' }}</span>
                                    </div>
                                    <div class="phase-title">{{ $milestone['title'] ?? 'Milestone' }}</div>
                                </div>
                                <div class="phase-deliverables">{{ $milestone['deliverables'] ?? 'Deliverables schedule' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="font-size: 11px; color: var(--text-subtle); margin-top: auto; padding-top: 24px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between;">
                    <span>Sheet 02 of 03 &bull; Architectural Blueprint</span>
                    <span>Musoftware Technical Proposal &copy; {{ date('Y') }}</span>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SHEET 3: ITEMIZED SCOPE, REFINED FINANCIAL LEDGER & EXECUTION     -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <section class="page-sheet" id="scope-page">
            <div class="sheet-bg-graphics" aria-hidden="true">
                <svg width="100%" height="100%" viewBox="0 0 900 1100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="1000" r="320" stroke="#0f172a" stroke-width="1" stroke-dasharray="3 6" opacity="0.03" />
                    <circle cx="50" cy="1000" r="240" stroke="#0284c7" stroke-width="1" opacity="0.04" />
                </svg>
            </div>

            <div class="sheet-content">
                <!-- Inner Page Header -->
                <header class="inner-header">
                    <div class="inner-header-title">
                        <h2>Section 02: Itemized Engineering Scope &amp; Commercial Ledger</h2>
                        <span>Transparent Khamsat-aligned rate cards &bull; Milestone verifiable deliverables</span>
                    </div>

                    <div class="inner-quote-id">
                        REF: {{ $quote['code'] ?? 'QT-VERIFIED' }}
                    </div>
                </header>

                <!-- Executive Scope Table -->
                <div class="table-container">
                    <table class="executive-table">
                        <thead>
                            <tr>
                                <th style="width: 52%;">Deliverable &amp; Engineering Scope</th>
                                <th style="width: 24%;" class="center">Platform Metric</th>
                                <th style="width: 24%;">Investment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Platform Category Header -->
                            <tr class="category-row">
                                <td colspan="3">1. Core Application Architecture &amp; Interfaces</td>
                            </tr>

                            @foreach($quote['platform_items'] ?? [] as $platform)
                                <tr>
                                    <td>
                                        <div class="item-main">
                                            <span class="item-name">{{ $platform['title'] }}</span>
                                            <span class="item-desc">
                                                {{ $platform['count'] }} {{ $platform['unit'] ?? 'Unit' }}s &bull; Rate ${{ $platform['rate'] ?? ($platform['rate_usd'] ?? 10) }}/{{ strtolower($platform['unit'] ?? 'unit') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="center">
                                        <span class="unit-tag">{{ $platform['count'] }} {{ $platform['unit'] ?? 'Unit' }}s</span>
                                    </td>
                                    <td class="price-col">
                                        {{ $quote['formatter']($platform['cost'] ?? ($platform['total_usd'] ?? 0)) }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Addons Category Header -->
                            @if(!empty($quote['itemized_addons']))
                                <tr class="category-row">
                                    <td colspan="3">2. Specialized Infrastructure, Integrations &amp; Automation</td>
                                </tr>

                                @foreach($quote['itemized_addons'] as $addon)
                                    <tr>
                                        <td>
                                            <div class="item-main">
                                                <span class="item-name">{{ $addon['title'] }}</span>
                                                @if(!empty($addon['desc'] ?? ($addon['description'] ?? null)))
                                                    <span class="item-desc">{{ $addon['desc'] ?? $addon['description'] }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="center">
                                            <span class="unit-tag">Module / Integration</span>
                                        </td>
                                        <td class="price-col">
                                            {{ $quote['formatter']($addon['cost'] ?? ($addon['price_usd'] ?? 0)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- ── REFINED INTERNATIONAL FINANCIAL LEDGER (المواصفات العالمية) ── -->
                <div class="ledger-wrapper">
                    <div class="ledger-box">
                        <div class="ledger-header">
                            <span class="title">Commercial Investment Schedule</span>
                            <span class="badge">Fixed Scope</span>
                        </div>

                        <div class="ledger-row">
                            <span>Base Engineering Scope:</span>
                            <span class="val">{{ $quote['formatter']($quote['subtotal_usd'] ?? 0) }}</span>
                        </div>

                        @if(!empty($quote['discount_usd']) && $quote['discount_usd'] > 0)
                            <div class="ledger-row discount">
                                <span>Architecture Bundle Incentive:</span>
                                <span class="val">-{{ $quote['formatter']($quote['discount_usd']) }}</span>
                            </div>
                        @endif

                        <div class="ledger-row grand-total">
                            <span>Total Fixed Investment:</span>
                            <span class="val">{{ $quote['formatter']($quote['total_usd'] ?? 0) }}</span>
                        </div>

                        @if($quote['is_usd'] ?? true)
                            <div class="equivalent-note">
                                Dual-Currency Benchmark: ~{{ number_format($quote['total_egp'] ?? 0) }} EGP (At 1 USD = {{ $quote['exchange_rate'] ?? 50 }} EGP)
                            </div>
                        @else
                            <div class="equivalent-note">
                                USD Base Equivalent: ${{ number_format($quote['total_usd'] ?? 0) }}
                            </div>
                        @endif

                        <!-- Milestone Payment Governance Schedule -->
                        <div class="milestone-schedule-box">
                            <span class="milestone-schedule-title">Milestone Payment Governance</span>
                            <div class="milestone-schedule-item">
                                <span>1. Kickoff Sprint &amp; Prototype (50%)</span>
                                <span class="amt">{{ $quote['formatter'](($quote['total_usd'] ?? 0) * 0.50) }}</span>
                            </div>
                            <div class="milestone-schedule-item">
                                <span>2. Beta Review &amp; Integrations (25%)</span>
                                <span class="amt">{{ $quote['formatter'](($quote['total_usd'] ?? 0) * 0.25) }}</span>
                            </div>
                            <div class="milestone-schedule-item">
                                <span>3. Production Handover &amp; Sign-off (25%)</span>
                                <span class="amt">{{ $quote['formatter'](($quote['total_usd'] ?? 0) * 0.25) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Official Execution Signatures -->
                <footer class="signatures-grid">
                    <div class="sign-block">
                        <div class="sign-rule"></div>
                        <div class="signer-name">
                            <img src="{{ asset('favicon.svg') }}" alt="" style="width: 14px; height: 14px; vertical-align: -1px; margin-right: 5px; display: inline-block;">Mahmoud Amin M.
                        </div>
                        <div class="signer-title">Principal Systems Architect &bull; Musoftware</div>
                    </div>

                    <div class="sign-block">
                        <div class="sign-rule"></div>
                        <div class="signer-name">{{ $quote['client_business'] ?? 'Authorized Executive Signature' }}</div>
                        <div class="signer-title">Client Acceptance &amp; Project Authorization</div>
                    </div>
                </footer>

                <div style="font-size: 11px; color: var(--text-subtle); margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between;">
                    <span>Sheet 03 of 03 &bull; Scope &amp; Commercials</span>
                    <span>Official Verified Proposal &bull; Musoftware Enterprise</span>
                </div>
            </div>
        </section>

    </main>

</body>
</html>
