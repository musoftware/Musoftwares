import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

interface DirectoryItem {
    name: string;
    category: string;
    desc: string;
    icon: React.ReactNode;
    href: string;
    btnText: string;
}

export default function Directory() {
    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState('ALL');

    // Dynamic style attachment
    useEffect(() => {
        const bootstrapLink = document.createElement('link');
        bootstrapLink.rel = 'stylesheet';
        bootstrapLink.href = '/v8main/css/bootstrap.min.css';
        bootstrapLink.id = 'v8main-bootstrap';

        const styleLink = document.createElement('link');
        styleLink.rel = 'stylesheet';
        styleLink.href = '/v8main/css/style.css?v=1.1';
        styleLink.id = 'v8main-style';

        document.head.appendChild(bootstrapLink);
        document.head.appendChild(styleLink);

        return () => {
            document.getElementById('v8main-bootstrap')?.remove();
            document.getElementById('v8main-style')?.remove();
        };
    }, []);

    // Scaler hook for wide screens
    useEffect(() => {
        const applySmartViewportScaling = () => {
            const w = window.innerWidth;
            const h = window.innerHeight;

            if (w <= 768) {
                document.body.style.zoom = '0.75';
                return;
            }

            if (w <= 1365) {
                document.body.style.zoom = '0.85';
                return;
            }

            const scaleX = w / 1366;
            const scaleY = h / 850;
            const computedScale = Math.min(scaleX, scaleY) * 0.92;
            const finalZoom = Math.min(1.40, Math.max(0.90, computedScale)).toFixed(3);
            document.body.style.zoom = finalZoom;
        };

        applySmartViewportScaling();
        window.addEventListener('resize', applySmartViewportScaling);
        return () => {
            window.removeEventListener('resize', applySmartViewportScaling);
            document.body.style.zoom = '';
        };
    }, []);

    const directoryItems: DirectoryItem[] = [
        {
            name: 'ERP System',
            category: 'Core SaaS',
            desc: 'نظام إدارة المؤسسات والحسابات المالية والفواتير وشجرة الحسابات المتكاملة.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 8h10M7 12h10M7 16h6" /></svg>,
            href: '/sso/erp',
            btnText: 'Launch ERP ➔'
        },
        {
            name: 'CRM System',
            category: 'Core SaaS',
            desc: 'إدارة العملاء والقيادة، متابعة العروض وسجل التفاعلات والاتصالات والمراحل البيعية.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>,
            href: '/sso/crm',
            btnText: 'Launch CRM ➔'
        },
        {
            name: 'WhatsApp Sender',
            category: 'Marketing',
            desc: 'منصة إرسال وتأتمة الحملات الترويجية ورسائل الواتساب الجماعية للعملاء.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" /></svg>,
            href: '/whatsapp-sender',
            btnText: 'Open WhatsApp ➔'
        },
        {
            name: 'FB Marketing System',
            category: 'Marketing',
            desc: 'أدوات التسويق واستخراج البيانات وإدارة الحملات الإعلانية على فيسبوك.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>,
            href: '/fbmb',
            btnText: 'Open FB Marketing ➔'
        },
        {
            name: 'SMS Gateway',
            category: 'Messaging',
            desc: 'بوابة إرسال الرسائل النصية القصيرة OTP وإشعارات الفواتير والتحقق.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2" /><line x1="12" y1="18" x2="12.01" y2="18" /></svg>,
            href: '/sms-payment-gateway',
            btnText: 'Open SMS Gateway ➔'
        },
        {
            name: 'Booking System',
            category: 'Core SaaS',
            desc: 'منصة حجز المواعيد والاستشارات والجداول الزمانية والمواعيد التلقائية.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>,
            href: '/sso/bookingsys',
            btnText: 'Open Bookings ➔'
        },
        {
            name: 'Gold POS System',
            category: 'POS Engine',
            desc: 'نظام كاشير ونقاط بيع وتداول الذهب والمجوهرات ومتابعة أسعار البورصة الحية.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>,
            href: '/sso/goldsaversys',
            btnText: 'Open Gold POS ➔'
        },
        {
            name: 'Affiliate POS System',
            category: 'POS Engine',
            desc: 'نظام إدارة المسوقين ونقاط البيع بالعمولة وتوزيع الأرباح التلقائي.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><line x1="8.59" y1="13.51" x2="15.42" y2="17.49" /><line x1="15.41" y1="6.51" x2="8.59" y2="10.49" /></svg>,
            href: '/sso/affsys',
            btnText: 'Open Affiliate POS ➔'
        },
        {
            name: 'Contracts & Proposals',
            category: 'Legal',
            desc: 'إدارة العقود الإلكترونية وشروط الاتفاقيات والمقترحات الفنية الموثقة.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /></svg>,
            href: '/isaas/contracts',
            btnText: 'Open Contracts ➔'
        },
        {
            name: 'Marketplace Services',
            category: 'App Store',
            desc: 'كتالوج المتجر لشراء الإضافات والخدمات والملحقات والتكاملات البرمجية.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /></svg>,
            href: '/marketplace/services',
            btnText: 'Browse Marketplace ➔'
        },
        {
            name: 'Seller Portal',
            category: 'App Store',
            desc: 'بوابة البائعين لرفع ونشر أدواتك ومنتجاتك الرقمية في المتجر.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" /></svg>,
            href: '/marketplace/dashboard',
            btnText: 'Seller Portal ➔'
        },
        {
            name: 'Recharge Wallet',
            category: 'Finance',
            desc: 'شحن رصيد المحفظة عبر وسائل الدفع الإلكترونية واستخدام الرصيد في الاشتراكات.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2" /><line x1="1" y1="10" x2="23" y2="10" /></svg>,
            href: '/financial/add-balance',
            btnText: 'Add Balance ➔'
        },
        {
            name: 'Invoices & Settlements',
            category: 'Finance',
            desc: 'سجل الفواتير الصادرة والمستحقة وسداد المبالغ وتنزيل كشوفات الحساب.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /></svg>,
            href: '/billing/invoices',
            btnText: 'View Invoices ➔'
        },
        {
            name: 'Transactions Audit Log',
            category: 'Finance',
            desc: 'سجل حركة الحساب المالي والتسويات والإيداعات والسحوبات التفصيلية.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>,
            href: '/financial/transactions',
            btnText: 'View Log ➔'
        },
        {
            name: 'Vouchers & Promo Codes',
            category: 'Rewards',
            desc: 'شحن أكواد الخصم والقسائم الشرائية الترويجية وإيداع رصيد مجاني.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" /><line x1="7" y1="7" x2="7.01" y2="7" /></svg>,
            href: '/vouchers',
            btnText: 'Redeem Vouchers ➔'
        },
        {
            name: 'Earnings Withdrawals',
            category: 'Finance',
            desc: 'طلب سحب أرباحك وعمولات التسويق المكتسبة إلى حسابك البنكي أو المحفظة.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="19" x2="12" y2="5" /><polyline points="5 12 12 5 19 12" /></svg>,
            href: '/financial/withdrawals',
            btnText: 'Withdraw Funds ➔'
        },
        {
            name: 'Points & Rewards',
            category: 'Rewards',
            desc: 'استبدال نقاط النشاط والمكافآت برصيد مجاني أو اشتراكات أدوات إضافية.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>,
            href: '/points',
            btnText: 'Points Store ➔'
        },
        {
            name: 'KYC Account Verification',
            category: 'Security',
            desc: 'رفع مستندات إثبات الشخصية لتوثيق الحساب ورفع حدود السحب والعمليات.',
            icon: <svg viewBox="0 0 24 24" strokeWidth="1.8" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="M15 8h2m-2 4h2m-6 4h6M7 16c0-1 1-2 2-2s2 1 2 2" /></svg>,
            href: '/kyc',
            btnText: 'Verify Account ➔'
        }
    ];

    // Filter logic
    const filteredItems = directoryItems.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            item.desc.toLowerCase().includes(searchQuery.toLowerCase());
        const matchesCategory = activeCategory === 'ALL' || item.category.toUpperCase() === activeCategory.toUpperCase();
        return matchesSearch && matchesCategory;
    });

    const categories = ['ALL', 'CORE SAAS', 'MARKETING', 'MESSAGING', 'POS ENGINE', 'APP STORE', 'FINANCE', 'REWARDS', 'SECURITY', 'LEGAL'];

    return (
        <div className="min-h-screen pb-5" style={{ background: '#0d061a', color: '#f3e8ff' }}>
            <Head title="Systems & Tools Directory - Musoftwares" />

            {/* Header */}
            <header className="nav py-3" style={{ borderBottom: '1px solid rgba(138,79,255,0.2)', background: '#130924' }}>
                <div className="container-fluid px-4">
                    <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                            <Link href="/dashboard" className="btn btn-outline-secondary btn-sm mr-3 text-light" style={{ borderColor: 'rgba(138,79,255,0.4)', borderRadius: '8px' }}>
                                ← Command Center
                            </Link>
                            <img className="logo pointer" src="/favicon.svg" style={{ height: '28px', width: '28px', filter: 'drop-shadow(0 0 10px rgba(168, 85, 247, 0.8))' }} alt="Musoftwares" />
                            <span className="brand-name-text ml-2 d-none d-sm-inline" style={{ fontSize: '17px', fontWeight: 800, color: '#f3e8ff', letterSpacing: '0.5px' }}>Musoftware</span>
                            <span className="ml-3 font-weight-bold d-none d-md-inline" style={{ color: '#d8b4fe', fontSize: '15px', borderLeft: '1px solid rgba(138,79,255,0.3)', paddingLeft: '14px' }}>System Directory &amp; Features Index</span>
                        </div>
                        <div>
                            <span className="badge badge-pill badge-primary px-3 py-2" style={{ background: 'rgba(138,79,255,0.2)', border: '1px solid #8A4FFF', color: '#d8b4fe', fontSize: '11px', fontWeight: 'bold' }}>
                                All Applications &amp; Services
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            {/* Container content */}
            <div className="container py-5 px-3">
                <div className="text-center mb-5">
                    <h2 className="font-weight-bold" style={{ color: '#f3e8ff', letterSpacing: '1px' }}>
                        <i className="icon-grid mr-2" style={{ color: '#8A4FFF' }}></i> All Systems &amp; Tools Directory
                    </h2>
                    <p className="text-muted" style={{ maxWidth: '650px', margin: '0 auto', fontSize: '14px' }}>
                        دليل كامل لكافة منصات النظام والخدمات والأدوات المتاحة بحسابك. ابحث باسم الخدمة أو فلتر حسب التصنيف للوصول السريع.
                    </p>
                </div>

                {/* Instant search and Category Badges filter */}
                <div className="mb-5 p-4 rounded" style={{ background: '#130924', border: '1.5px solid rgba(138, 79, 255, 0.35)', boxShadow: '0 8px 25px rgba(0,0,0,0.5)' }}>
                    <div className="row align-items-center mb-4">
                        <div className="col-12">
                            <input
                                type="text"
                                className="form-control text-light py-2 px-3"
                                style={{ background: 'rgba(35, 16, 70, 0.4)', border: '1px solid rgba(168, 85, 247, 0.4)', borderRadius: '10px', outline: 'none' }}
                                placeholder="Search apps, tools, logs... (e.g. ERP, CRM, Wallet)"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>

                    {/* Category Filter Badges */}
                    <div className="d-flex flex-wrap gap-2 justify-content-center">
                        {categories.map((cat, idx) => (
                            <button
                                key={idx}
                                onClick={() => setActiveCategory(cat)}
                                className="btn btn-sm border-0 font-weight-bold"
                                style={{
                                    fontSize: '11px',
                                    borderRadius: '6px',
                                    padding: '5px 12px',
                                    background: activeCategory === cat ? 'linear-gradient(135deg, #8A4FFF, #a855f7)' : 'rgba(138, 79, 255, 0.12)',
                                    color: activeCategory === cat ? '#fff' : '#c084fc',
                                    border: activeCategory === cat ? 'none' : '1px solid rgba(138, 79, 255, 0.25)',
                                    transition: 'all 0.2s ease',
                                    cursor: 'pointer'
                                }}
                            >
                                {cat}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Directory cards listing */}
                <div className="row">
                    <div className="col-12">
                        {filteredItems.map((item, idx) => (
                            <div key={idx} className="directory-card d-flex align-items-center justify-content-between flex-wrap p-3 mb-3" style={{ background: '#130924', border: '1px solid rgba(138, 79, 255, 0.3)', borderRadius: '12px', boxShadow: '0 4px 15px rgba(0, 0, 0, 0.4)' }}>
                                <div className="d-flex align-items-center">
                                    <div className="hud-icon-box mr-3" style={{ width: '48px', height: '48px', borderRadius: '10px', background: 'rgba(22, 10, 42, 0.85)', border: '1px solid rgba(138, 79, 255, 0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                        <div style={{ width: '24px', height: '24px', color: '#8A4FFF' }}>
                                            {item.icon}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="d-flex align-items-center mb-1 flex-wrap gap-2">
                                            <h5 className="m-0 font-weight-bold text-light" style={{ fontSize: '15px' }}>{item.name}</h5>
                                            <span className="category-badge" style={{ fontSize: '9px', fontWeight: 700, padding: '2px 6px', borderRadius: '4px', background: 'rgba(138, 79, 255, 0.15)', color: '#c084fc', border: '1px solid rgba(138, 79, 255, 0.3)', textTransform: 'uppercase' }}>
                                                {item.category}
                                            </span>
                                        </div>
                                        <div style={{ color: '#d8b4fe', fontSize: '13px' }}>{item.desc}</div>
                                    </div>
                                </div>
                                <div className="mt-2 mt-md-0">
                                    <Link href={item.href} className="btn-launch" style={{ background: 'linear-gradient(135deg, #8A4FFF, #a855f7)', border: 'none', color: '#fff', fontWeight: 600, padding: '8px 18px', borderRadius: '8px', fontSize: '13px', textDecoration: 'none', display: 'inline-block' }}>
                                        {item.btnText}
                                    </Link>
                                </div>
                            </div>
                        ))}

                        {filteredItems.length === 0 && (
                            <div className="text-center text-muted py-5" style={{ background: '#130924', border: '1px dashed rgba(138, 79, 255, 0.3)', borderRadius: '12px' }}>
                                <i className="icon-grid d-block mb-2" style={{ fontSize: '32px', color: '#8A4FFF' }}></i>
                                No services found matching your criteria.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
