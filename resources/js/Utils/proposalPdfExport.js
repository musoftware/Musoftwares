import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import QRCode from 'qrcode';
import axios from 'axios';

/**
 * Generate and download an executive engineering proposal PDF
 * matching the authentic Kraft paper and high-contrast infographic design.
 */
export async function downloadProposalPdf({
    costBreakdown,
    selectedPlatforms,
    platformScreens,
    rates,
    exchangeRate = 50.0,
    isUsd = true,
    lang = 'ar', // 'ar' or 'en'
}) {
    // 1. Obtain official quotation code and URL from backend
    let code = 'QT-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.random().toString(36).substring(2, 7).toUpperCase();
    let proposalUrl = `https://www.musoftwares.com/estimator`;

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const summaryText = costBreakdown.itemizedPlatforms.map(p => p.title).join(' + ');

        const res = await axios.post('/estimator/generate-quotation', {
            platform_items: costBreakdown.itemizedPlatforms,
            itemized_addons: costBreakdown.itemizedAddons,
            subtotal_usd: costBreakdown.subtotalUsd,
            is_bundle_discount: costBreakdown.multiPlatformDiscount > 0,
            discount_usd: costBreakdown.multiPlatformDiscount,
            total_usd: costBreakdown.finalTotalUsd,
            total_egp: costBreakdown.finalTotalEgp,
            exchange_rate: exchangeRate,
            is_usd: isUsd,
            platforms_summary: summaryText,
        }, {
            headers: token ? { 'X-CSRF-TOKEN': token } : {}
        });

        if (res.data?.success && res.data.code) {
            code = res.data.code;
            proposalUrl = res.data.url || `https://www.musoftwares.com/quotation/${code}`;
        }
    } catch (e) {
        console.warn('Could not register quote code on backend, falling back to local code', e);
    }

    // 2. Generate QR code Data URL
    let qrDataUrl = '';
    try {
        qrDataUrl = await QRCode.toDataURL(proposalUrl, {
            margin: 1,
            width: 140,
            color: {
                dark: '#0f172a',
                light: '#f5efeb'
            }
        });
    } catch (e) {
        console.warn('QR code generation failed', e);
    }

    // Format helpers
    const formatCurrency = (usdVal) => {
        if (!isUsd) {
            const egp = Math.round(usdVal * exchangeRate);
            return `${egp.toLocaleString()} ${lang === 'ar' ? 'ج.م' : 'EGP'}`;
        }
        return `$${usdVal.toLocaleString()}`;
    };

    const isAr = lang === 'ar';
    const totalDisplay = formatCurrency(costBreakdown.finalTotalUsd);
    const subtotalDisplay = formatCurrency(costBreakdown.subtotalUsd);
    const discountDisplay = formatCurrency(costBreakdown.multiPlatformDiscount);
    const platformsTitle = costBreakdown.itemizedPlatforms.map(p => isAr ? (p.title_ar || p.title) : p.title).join(' + ') || (isAr ? 'نظام برمجي متكامل' : 'Custom Software System');

    // WhatsApp Direct Link
    const waPhone = '201015218548';
    const waMsg = isAr
        ? `مرحباً م. محمود أمين، أراجع العرض الهندسي الموثق #${code} لنظام (${platformsTitle}) بإجمالي ${totalDisplay}. أود التنسيق لبدء المشروع!`
        : `Hello Mahmoud, reviewing verified quotation #${code} for (${platformsTitle}) totaling ${totalDisplay}. Let's discuss proceeding with this scope!`;
    const waUrl = `https://wa.me/${waPhone}?text=${encodeURIComponent(waMsg)}`;

    // 3. Create hidden off-screen container matching authentic Kraft Paper styling
    const container = document.createElement('div');
    container.id = 'proposal-pdf-render-root';
    container.style.position = 'fixed';
    container.style.top = '-99999px';
    container.style.left = '-99999px';
    container.style.width = '850px';
    container.style.minHeight = '1200px';
    container.style.backgroundColor = '#F5EFEB';
    container.style.color = '#0F172A';
    container.style.zIndex = '-9999';
    container.style.boxSizing = 'border-box';
    container.style.fontFamily = "'Cairo', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
    container.setAttribute('dir', isAr ? 'rtl' : 'ltr');

    // Build the authentic kraft paper layout with the vivid orange highlight card
    container.innerHTML = `
        <div style="
            position: relative;
            padding: 48px 50px 42px;
            background-color: #F5EFEB;
            background-image: radial-gradient(#d8ccbe 0.75px, transparent 0.75px), radial-gradient(#d8ccbe 0.75px, #F5EFEB 0.75px);
            background-size: 24px 24px;
            background-position: 0 0, 12px 12px;
            color: #0F172A;
            box-sizing: border-box;
            overflow: hidden;
            width: 850px;
        ">
            <!-- Large Watermark Numeral (Matching reference image) -->
            <div style="
                position: absolute;
                bottom: 40px;
                ${isAr ? 'left: 40px;' : 'right: 40px;'}
                font-size: 210px;
                font-weight: 900;
                line-height: 0.8;
                color: rgba(15, 23, 42, 0.04);
                pointer-events: none;
                user-select: none;
                z-index: 1;
                font-family: 'JetBrains Mono', monospace;
            ">01</div>

            <!-- Top Header Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid rgba(15, 23, 42, 0.15); padding-bottom: 16px; margin-bottom: 28px; position: relative; z-index: 2;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="/favicon.svg" alt="Musoftware Logo" style="width: 32px; height: 32px; object-fit: contain;" />
                    <div>
                        <div style="font-size: 14px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #0F172A;">MUSOFTWARE ARCHITECTURE</div>
                        <div style="font-size: 10px; color: rgba(15, 23, 42, 0.6); font-weight: 600;">Systems &amp; Enterprise Platforms &bull; Est. 2020</div>
                    </div>
                </div>

                <div style="text-align: ${isAr ? 'left' : 'right'};">
                    <div style="font-size: 11px; font-weight: 800; color: #0284C7; text-transform: uppercase; letter-spacing: 1px;">
                        ${isAr ? 'دراسة جدوى هندسية وتكلفة تنفيذية' : 'TECHNICAL SCOPE & BUDGET PROPOSAL'}
                    </div>
                    <div style="font-size: 10px; color: rgba(15, 23, 42, 0.6); font-weight: 700; font-family: monospace;">
                        ${code} &bull; ${new Date().toLocaleDateString(isAr ? 'ar-EG' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                    </div>
                </div>
            </div>

            <!-- Hero Typographic Hook (Like reference: عند 50-300 محادثة شهرياً) -->
            <div style="margin-bottom: 26px; position: relative; z-index: 2;">
                <div style="font-size: 11px; font-weight: 800; color: #FF5722; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                    ${isAr ? '● نطاق العمل الهندسي المعتمد' : '● VERIFIED ARCHITECTURAL SCOPE'}
                </div>
                <h1 style="font-size: 34px; font-weight: 900; line-height: 1.25; color: #0F172A; margin: 0 0 10px; letter-spacing: -0.5px;">
                    ${platformsTitle}
                </h1>
                <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; color: rgba(15, 23, 42, 0.7); font-weight: 600;">
                    <span>⏱ ${isAr ? 'الجدول الزمني التقديري:' : 'Estimated Timeline:'} <strong style="color: #0284C7;">~${costBreakdown.estimatedDays} ${isAr ? 'يوم عمل' : 'business days'}</strong></span>
                    <span>•</span>
                    <span>💰 ${isAr ? 'الاستثمار الإجمالي:' : 'Total Investment:'} <strong style="color: #0F172A;">${totalDisplay}</strong></span>
                    <span>•</span>
                    <span>🛡 ${isAr ? 'ضمان الملكية:' : 'Ownership:'} <strong style="color: #10B981;">${isAr ? 'كود مصدري ملكية 100%' : '100% Full Source Code'}</strong></span>
                </div>
            </div>

            <!-- ── THE VIVID ORANGE HIGHLIGHT CARD (Exact match to user reference image) ── -->
            <div style="
                background-color: #FF5722;
                border-radius: 18px;
                padding: 28px 30px;
                color: #ffffff;
                margin-bottom: 30px;
                position: relative;
                box-shadow: 0 12px 30px -10px rgba(255, 87, 34, 0.35);
                z-index: 2;
            ">
                <!-- Card Header -->
                <div style="font-size: 22px; font-weight: 900; line-height: 1.3; margin-bottom: 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.25); padding-bottom: 12px;">
                    ${isAr ? 'المواصفات الجوهرية للـ MVP الأول' : 'Core Phase 1 MVP Scope & Deliverables'}
                </div>

                <!-- Card Checklist with Green Checks -->
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px; font-weight: 700; line-height: 1.4;">
                    ${costBreakdown.itemizedPlatforms.map(platform => `
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <span style="background: #ffffff; color: #10B981; border-radius: 6px; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; flex-shrink: 0; margin-top: 1px;">✓</span>
                            <span>${platform.title}: ${platform.count} ${isAr ? (platform.unit === 'Page' ? 'صفحة متجاوبة' : 'شاشة تفاعلية') : `${platform.unit}s`}</span>
                        </div>
                    `).join('')}

                    ${costBreakdown.itemizedAddons.slice(0, 4).map(addon => `
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <span style="background: #ffffff; color: #10B981; border-radius: 6px; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; flex-shrink: 0; margin-top: 1px;">✓</span>
                            <span>${addon.title}</span>
                        </div>
                    `).join('')}

                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <span style="background: #ffffff; color: #10B981; border-radius: 6px; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; flex-shrink: 0; margin-top: 1px;">✓</span>
                        <span>${isAr ? 'لوحة تحكم إدارية مركزية وبنية تحتية سحابية عالية الأمان' : 'Central Admin Dashboard & High-Performance Cloud Architecture'}</span>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <span style="background: #ffffff; color: #10B981; border-radius: 6px; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; flex-shrink: 0; margin-top: 1px;">✓</span>
                        <span>${isAr ? 'ملكية الكود المصدر وقواعد البيانات 100% بدون أي اشتراكات خفية' : '100% Full Source Code & Database Ownership with Zero Lock-in'}</span>
                    </div>
                </div>

                <!-- Golden Takeaway / MVP Punchline (Matching image punchline: ما تدفع 30$ شهريا لـ CRM...) -->
                <div style="
                    margin-top: 20px;
                    padding-top: 16px;
                    border-top: 1.5px dashed rgba(255, 255, 255, 0.4);
                    font-size: 15px;
                    font-weight: 900;
                    line-height: 1.45;
                    color: #ffffff;
                ">
                    ${isAr
                        ? '💡 نصيحة مهندس الأنظمة: ما تدفع آلاف الدولارات على أنظمة ضخمة وميزات معقدة وأنت لسا ما اختبرت السوق بالـ MVP الأساسي اللي يرجعلك استثمارك بأسرع وقت!'
                        : '💡 System Architect Insight: Avoid massive upfront costs for complex unproven features. Launch the core MVP first to validate demand, retain 30-50% cash reserve, and scale upon real user traction!'}
                </div>
            </div>

            <!-- Two-Column Architecture Ledger & Milestone Governance -->
            <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 24px; margin-bottom: 28px; position: relative; z-index: 2;">
                
                <!-- Left: Scope Breakdown Table -->
                <div style="background: rgba(255, 255, 255, 0.75); border: 1.5px solid rgba(15, 23, 42, 0.1); border-radius: 16px; padding: 20px 22px;">
                    <div style="font-size: 13px; font-weight: 800; color: #0F172A; margin-bottom: 12px; display: flex; justify-content: space-between;">
                        <span>${isAr ? 'تفصيل بنود الاستثمار' : 'Investment Line Items'}</span>
                        <span style="color: #0284C7; font-weight: 700;">${isAr ? 'نطاق ثابت' : 'Fixed Scope'}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 11.5px;">
                        ${costBreakdown.itemizedPlatforms.map(p => `
                            <div style="display: flex; justify-content: space-between; color: rgba(15, 23, 42, 0.8); border-bottom: 1px solid rgba(15, 23, 42, 0.06); padding-bottom: 6px;">
                                <span>${p.title} (${p.count} ${p.unit})</span>
                                <strong style="color: #0F172A;">${formatCurrency(p.total)}</strong>
                            </div>
                        `).join('')}

                        ${costBreakdown.itemizedAddons.map(a => `
                            <div style="display: flex; justify-content: space-between; color: rgba(15, 23, 42, 0.7); border-bottom: 1px solid rgba(15, 23, 42, 0.06); padding-bottom: 6px;">
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px;">${a.title}</span>
                                <strong style="color: #0F172A;">+${formatCurrency(a.subtotal)}</strong>
                            </div>
                        `).join('')}

                        ${costBreakdown.multiPlatformDiscount > 0 ? `
                            <div style="display: flex; justify-content: space-between; color: #0284C7; font-weight: 700; padding-top: 4px;">
                                <span>${isAr ? 'خصم باقة المنصات المتعددة (10%)' : 'Multi-Platform Bundle Discount (10%)'}</span>
                                <span>-${discountDisplay}</span>
                            </div>
                        ` : ''}

                        <div style="display: flex; justify-content: space-between; color: #0F172A; font-weight: 900; font-size: 15px; border-top: 2px solid #0F172A; padding-top: 8px; margin-top: 4px;">
                            <span>${isAr ? 'إجمالي الاستثمار الهندسي:' : 'Total Investment:'}</span>
                            <span style="color: #0F172A;">${totalDisplay}</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Milestone Governance & Verification -->
                <div style="background: rgba(255, 255, 255, 0.75); border: 1.5px solid rgba(15, 23, 42, 0.1); border-radius: 16px; padding: 20px 22px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="font-size: 13px; font-weight: 800; color: #0F172A; margin-bottom: 12px;">
                            ${isAr ? 'حوكمة الدفعات الثلاثية' : 'Milestone Governance'}
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 11px;">
                            <div style="display: flex; justify-content: space-between; padding: 6px 10px; background: rgba(2, 132, 199, 0.08); border-radius: 8px;">
                                <span>1. ${isAr ? 'انطلاق المشروع والتصميم (50%)' : 'Kickoff & UI Architecture (50%)'}</span>
                                <strong style="color: #0284C7;">${formatCurrency(costBreakdown.finalTotalUsd * 0.5)}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 10px; background: rgba(15, 23, 42, 0.04); border-radius: 8px;">
                                <span>2. ${isAr ? 'النسخة التجريبية والربط (25%)' : 'Beta Prototype & Integrations (25%)'}</span>
                                <strong>${formatCurrency(costBreakdown.finalTotalUsd * 0.25)}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 10px; background: rgba(16, 185, 129, 0.08); border-radius: 8px;">
                                <span>3. ${isAr ? 'التدشين وتسليم الكود (25%)' : 'Production Handover (25%)'}</span>
                                <strong style="color: #10B981;">${formatCurrency(costBreakdown.finalTotalUsd * 0.25)}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Direct QR Code & Verification -->
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(15, 23, 42, 0.08);">
                        ${qrDataUrl ? `<img src="${qrDataUrl}" alt="Verification QR Code" style="width: 60px; height: 60px; border-radius: 8px; border: 1px solid rgba(15, 23, 42, 0.1);" />` : ''}
                        <div>
                            <div style="font-size: 10px; font-weight: 800; color: #0F172A;">${isAr ? 'معاينة واعتماد أونلاين' : 'Online Verification'}</div>
                            <div style="font-size: 9px; color: rgba(15, 23, 42, 0.6);">${isAr ? 'امسح الكود بكاميرا هاتفك لمعاينة وتأكيد النطاق' : 'Scan with camera to review live scope'}</div>
                            <div style="font-size: 9px; font-weight: 700; color: #0284C7; font-family: monospace; margin-top: 2px;">wa.me/${waPhone}</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Signatures & Authority Endorsement -->
            <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 16px; border-top: 1.5px solid rgba(15, 23, 42, 0.15); margin-bottom: 20px; position: relative; z-index: 2;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #0F172A;">MAHMOUD AMIN M.</div>
                    <div style="font-size: 10px; color: rgba(15, 23, 42, 0.6); font-weight: 600;">Principal Systems Architect &bull; Musoftware Enterprise</div>
                    <div style="font-size: 9px; color: #10B981; font-weight: 700; margin-top: 2px;">✔ Digitally Verified Architecture Lead</div>
                </div>

                <div style="text-align: ${isAr ? 'left' : 'right'};">
                    <div style="font-size: 10px; color: rgba(15, 23, 42, 0.6); font-weight: 600;">${isAr ? 'صلاحية المقترح الهندسي:' : 'Proposal Validity:'}</div>
                    <div style="font-size: 11px; font-weight: 800; color: #0F172A;">30 ${isAr ? 'يوماً من تاريخ الإصدار' : 'Days from issuance'}</div>
                </div>
            </div>

            <!-- ── PRODUCT-LED GROWTH (VIRAL SELF-MARKETING BANNER) ── -->
            <div style="
                background: #0F172A;
                color: #ffffff;
                border-radius: 12px;
                padding: 12px 18px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 11px;
                font-weight: 600;
                position: relative;
                z-index: 2;
            ">
                <span>
                    ${isAr
                        ? '🚀 صُممت هذه الدراسة الهندسية عبر حاسبة Musoftware التفاعلية — خطط ميزانية مشروعك مجاناً عبر:'
                        : '🚀 Engineered via the Musoftware Interactive Architecture Estimator — calculate your project for free:'}
                    <strong style="color: #38BDF8; margin-${isAr ? 'right' : 'left'}: 4px;">musoftwares.com/estimator</strong>
                </span>
                <span style="font-size: 10px; opacity: 0.8; font-family: monospace;">#${code}</span>
            </div>

        </div>
    `;

    document.body.appendChild(container);

    try {
        // Wait for images and fonts to render
        await new Promise(resolve => setTimeout(resolve, 350));

        const canvas = await html2canvas(container, {
            scale: 2, // Retina resolution (crisp text & SVG)
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#F5EFEB',
            logging: false,
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
        });

        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);

        const fileName = `Musoftware-Estimate-${code}-${lang.toUpperCase()}.pdf`;
        pdf.save(fileName);

        return {
            success: true,
            code,
            fileName,
            proposalUrl,
        };
    } finally {
        if (container.parentNode) {
            container.parentNode.removeChild(container);
        }
    }
}
