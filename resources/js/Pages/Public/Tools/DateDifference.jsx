import { useState, useRef, useMemo } from 'react';
import { usePage, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Calendar, Clock, ArrowRight, Sparkles, MessageSquare, Landmark, Check } from 'lucide-react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

gsap.registerPlugin(ScrollTrigger);

export default function DateDifference() {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";
    const [scopingPrompt, setScopingPrompt] = useState('');

    const locale = typeof document !== 'undefined' ? document.documentElement.lang || 'en' : 'en';

    // Get today and tomorrow dates in YYYY-MM-DD formatted strings
    const getTodayString = () => {
        const today = new Date();
        return today.toISOString().split('T')[0];
    };

    const getTomorrowString = () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    };

    const [startDate, setStartDate] = useState(getTodayString());
    const [endDate, setEndDate] = useState(getTomorrowString());

    useGSAP(() => {
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {
            const elements = section.querySelectorAll('.gsap-fade-up');
            gsap.fromTo(elements, 
                { opacity: 0, y: 30 },
                {
                    opacity: 1, 
                    y: 0, 
                    duration: 0.8,
                    stagger: 0.1,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: section,
                        start: "top 85%",
                        toggleActions: "play none none reverse"
                    }
                }
            );
        });
    }, { scope: mainRef });

    // --- Dynamic Date Calculations ---
    const parseLocalMidnight = (dateStr) => {
        const parts = dateStr.split('-');
        if (parts.length !== 3) return new Date(dateStr);
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    };

    const diffResults = useMemo(() => {
        const start = parseLocalMidnight(startDate);
        const end = parseLocalMidnight(endDate);

        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            return null;
        }

        const diffTime = end.getTime() - start.getTime();
        const totalMs = Math.abs(diffTime);

        const totalMinutes = Math.floor(totalMs / (1000 * 60));
        const totalHours = Math.floor(totalMs / (1000 * 60 * 60));
        const totalDays = Math.floor(totalMs / (1000 * 60 * 60 * 24));
        const totalWeeks = Math.floor(totalDays / 7);

        // Advanced breakdown
        let years = end.getFullYear() - start.getFullYear();
        let months = end.getMonth() - start.getMonth();
        let days = end.getDate() - start.getDate();

        if (days < 0) {
            months -= 1;
            // Get previous month's total days
            const prevMonth = new Date(end.getFullYear(), end.getMonth(), 0);
            days += prevMonth.getDate();
        }

        if (months < 0) {
            years -= 1;
            months += 12;
        }

        // Hijri Conversion using Intl
        const toHijri = (dateObj) => {
            try {
                return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-SA-u-ca-islamic' : 'en-US-u-ca-islamic', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }).format(dateObj);
            } catch (e) {
                return '';
            }
        };

        const startHijri = toHijri(start);
        const endHijri = toHijri(end);

        return {
            totalDays,
            totalWeeks,
            totalHours,
            totalMinutes,
            years: Math.abs(years),
            months: Math.abs(months),
            days: Math.abs(days),
            startHijri,
            endHijri,
            isNegative: diffTime < 0
        };
    }, [startDate, endDate, locale]);

    const handleScopingSubmit = (e) => {
        e.preventDefault();
        if (!scopingPrompt.trim()) return;

        const finalPrompt = `Client requirements details about Booking/Scheduling system:
${scopingPrompt}

Calculation Context:
- User was checking Date Difference calculator from ${startDate} to ${endDate}.`;

        router.visit(`/register?prefill_desc=${encodeURIComponent(finalPrompt.trim())}`);
    };

    // JSON-LD structured data for Google Search Indexing
    const jsonLd = {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": __('tools.date_title'),
        "description": __('tools.date_desc'),
        "applicationCategory": "UtilityApplication",
        "operatingSystem": "All",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    };

    return (
        <PublicLayout>
            <SeoHead 
                title={`${__('tools.date_title')} | Musoftware`}
                description={__('tools.date_desc')}
                jsonLd={jsonLd}
            />

            <FloatingWhatsAppButton 
                phoneNumber={phoneNumber} 
                defaultMessage="Hello Mahmoud, I'm interested in building a custom calendar, scheduling rule, or booking portal." 
            />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white pt-24 pb-16 lg:pt-36">
                
                {/* Hero Header */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 mb-16 text-center">
                    <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white mx-auto">
                        <Calendar className="h-3 w-3 text-slate-800" />
                        {__('tools.tools_directory') || 'Free Tools'}
                    </div>
                    <h1 className="gsap-fade-up text-4xl lg:text-6xl font-extrabold text-[#111111] tracking-tight mb-4">
                        {__('tools.date_title')}
                    </h1>
                    <p className="gsap-fade-up text-lg text-[#666666] max-w-3xl mx-auto font-normal leading-relaxed">
                        {__('tools.date_desc')}
                    </p>
                </section>

                {/* Calculator Inputs and Results */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-8 mb-20">
                    
                    {/* Inputs Card (Left) */}
                    <div className="lg:col-span-5">
                        <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all">
                            <CardHeader>
                                <CardTitle className="text-xl font-bold flex items-center gap-2 text-slate-900">
                                    <Clock className="h-5 w-5 text-slate-500" />
                                    Configure Dates
                                </CardTitle>
                                <CardDescription>Select start and end dates to compute the time differences.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="startDate">{__('tools.date_start')}</Label>
                                    <Input 
                                        id="startDate" 
                                        type="date" 
                                        value={startDate} 
                                        onChange={(e) => setStartDate(e.target.value)}
                                        className="border-slate-200 focus-visible:ring-slate-900"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="endDate">{__('tools.date_end')}</Label>
                                    <Input 
                                        id="endDate" 
                                        type="date" 
                                        value={endDate} 
                                        onChange={(e) => setEndDate(e.target.value)}
                                        className="border-slate-200 focus-visible:ring-slate-900"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Results Panel (Right) */}
                    <div className="lg:col-span-7">
                        {diffResults ? (
                            <div className="space-y-6">
                                
                                {/* Dynamic Detailed Breakdown */}
                                <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
                                    <CardHeader className="border-b border-slate-100 bg-slate-50/50">
                                        <CardTitle className="text-lg font-bold text-slate-900 flex items-center gap-2">
                                            <Calendar className="h-5 w-5 text-slate-600" />
                                            {__('tools.date_diff_result')}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="pt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                                        
                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100/80">
                                            <span className="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider block mb-1">{__('tools.date_years')}</span>
                                            <span className="text-2xl font-black text-slate-900">{diffResults.years}</span>
                                        </div>

                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100/80">
                                            <span className="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider block mb-1">{__('tools.date_months')}</span>
                                            <span className="text-2xl font-black text-slate-900">{diffResults.months}</span>
                                        </div>

                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100/80">
                                            <span className="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider block mb-1">{__('tools.date_days')}</span>
                                            <span className="text-2xl font-black text-slate-900">{diffResults.days}</span>
                                        </div>

                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100/80">
                                            <span className="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider block mb-1">{__('tools.date_weeks')}</span>
                                            <span className="text-2xl font-black text-slate-900">{diffResults.totalWeeks.toLocaleString()}</span>
                                        </div>

                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100/80">
                                            <span className="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider block mb-1">Total {__('tools.date_days')}</span>
                                            <span className="text-2xl font-black text-slate-900">{diffResults.totalDays.toLocaleString()}</span>
                                        </div>

                                        <div className="p-4 bg-slate-50 rounded-xl border border-slate-100/80">
                                            <span className="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider block mb-1">Total {__('tools.date_hours')}</span>
                                            <span className="text-2xl font-black text-slate-900">{diffResults.totalHours.toLocaleString()}</span>
                                        </div>

                                    </CardContent>
                                </Card>

                                {/* Hijri estimation panel */}
                                <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
                                    <CardHeader className="pb-4">
                                        <CardTitle className="text-sm font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <Landmark className="h-4 w-4 text-slate-500" />
                                            {__('tools.date_hijri_estimation')}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4 font-medium text-slate-800 text-sm">
                                        <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                            <span>{__('tools.date_hijri_start')}</span>
                                            <span className="font-bold text-slate-950">{diffResults.startHijri}</span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span>{__('tools.date_hijri_end')}</span>
                                            <span className="font-bold text-slate-950">{diffResults.endHijri}</span>
                                        </div>
                                    </CardContent>
                                </Card>

                            </div>
                        ) : (
                            <div className="p-8 text-center text-slate-400 font-bold border border-dashed rounded-2xl border-slate-200">
                                Please select valid dates.
                            </div>
                        )}
                    </div>
                </section>

                {/* Conversion Funnel Box */}
                <section className="reveal-section max-w-4xl mx-auto px-6 lg:px-8">
                    <div className="gsap-fade-up bg-slate-900 rounded-3xl p-8 lg:p-12 text-white border border-slate-800 relative overflow-hidden shadow-2xl">
                        
                        <div className="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                            <Sparkles className="h-36 w-36" />
                        </div>
                        
                        <div className="relative z-10 max-w-2xl">
                            <h2 className="text-2xl lg:text-3xl font-extrabold mb-4 tracking-tight leading-tight">
                                {__('tools.date_cta_title')}
                            </h2>
                            <p className="text-slate-300 text-sm lg:text-base mb-8 leading-relaxed font-normal">
                                {__('tools.date_cta_desc')}
                            </p>

                            <form onSubmit={handleScopingSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="scopingPrompt" className="text-slate-300 font-semibold text-xs uppercase tracking-wider flex items-center gap-1">
                                        <MessageSquare className="h-3 w-3 text-slate-400" />
                                        Describe your custom booking or slot rules:
                                    </Label>
                                    <div className="flex flex-col sm:flex-row gap-3">
                                        <Input
                                            id="scopingPrompt"
                                            value={scopingPrompt}
                                            onChange={(e) => setScopingPrompt(e.target.value)}
                                            placeholder="e.g. A booking system for clinic appointments that limits doctors to 5 slots..."
                                            className="bg-slate-800/80 border-slate-700 text-white placeholder-slate-500 focus-visible:ring-white h-11 flex-1"
                                        />
                                        <Button
                                            type="submit"
                                            className="bg-white hover:bg-slate-100 text-slate-950 font-bold h-11 px-6 shrink-0"
                                        >
                                            {__('tools.date_cta_btn')}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}
