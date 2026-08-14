import { useState, useRef, useMemo } from 'react';
import { usePage, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Sparkles, MessageSquare, Calculator, HelpCircle, Check, Info, Landmark } from 'lucide-react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

gsap.registerPlugin(ScrollTrigger);

export default function FacebookCostCalculator() {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";
    const [scopingPrompt, setScopingPrompt] = useState('');

    // --- State for Calculator Inputs ---
    const [followers, setFollowers] = useState(5000); // Slider 500 to 500,000
    const [postsPerWeek, setPostsPerWeek] = useState(3); // Slider 1 to 14
    const [chatbotLevel, setChatbotLevel] = useState('basic'); // none, basic, ai
    const [manageAds, setManageAds] = useState(false); // true, false
    
    // Currency toggle
    const [isEgp, setIsEgp] = useState(false);
    const usdToEgpRate = 50.0; // Fixed illustrative rate for premium local helper

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

    // --- Dynamic Pricing Formula ---
    const prices = useMemo(() => {
        // 1. Base Management Fee (depends on page size/followers)
        let baseFee = 100;
        if (followers >= 10000 && followers < 50000) {
            baseFee = 180;
        } else if (followers >= 50000 && followers < 250000) {
            baseFee = 350;
        } else if (followers >= 250000) {
            baseFee = 600;
        }

        // 2. Content Creation (posts per week * 4.33 weeks per month * $12 per post)
        const postsCost = Math.round(postsPerWeek * 4.33 * 12);

        // 3. Chatbot implementation
        let chatbotCost = 0;
        if (chatbotLevel === 'basic') {
            chatbotCost = 45;
        } else if (chatbotLevel === 'ai') {
            chatbotCost = 220;
        }

        // 4. Ads management
        const adsCost = manageAds ? 120 : 0;

        const totalUsd = baseFee + postsCost + chatbotCost + adsCost;

        return {
            base: baseFee,
            posts: postsCost,
            chatbot: chatbotCost,
            ads: adsCost,
            total: totalUsd
        };
    }, [followers, postsPerWeek, chatbotLevel, manageAds]);

    const formatCurrency = (amountUsd) => {
        if (isEgp) {
            const amountEgp = amountUsd * usdToEgpRate;
            return new Intl.NumberFormat('ar-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(amountEgp);
        }
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(amountUsd);
    };

    const handleScopingSubmit = (e) => {
        e.preventDefault();
        if (!scopingPrompt.trim()) return;
        
        // Include estimated cost configuration in prefill info
        const finalPrompt = `Client calculated Facebook Cost Estimation:
- Followers: ${followers.toLocaleString()}
- Posts/Week: ${postsPerWeek}
- Chatbot: ${chatbotLevel}
- Ads Managed: ${manageAds ? 'Yes' : 'No'}
- Estimated Cost: $${prices.total}/mo

Client requirements details:
${scopingPrompt}`;

        router.visit(`/register?prefill_desc=${encodeURIComponent(finalPrompt.trim())}`);
    };

    // JSON-LD structured data for Google Search Indexing
    const jsonLd = {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": __('tools.fb_title'),
        "description": __('tools.fb_desc'),
        "applicationCategory": "BusinessApplication",
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
                title={`${__('tools.fb_title')} | Musoftware`}
                description={__('tools.fb_desc')}
                jsonLd={jsonLd}
            />

            <FloatingWhatsAppButton 
                phoneNumber={phoneNumber} 
                defaultMessage={`Hello Mahmoud, I calculated my Facebook management cost at $${prices.total}/mo and want to discuss custom chatbot and social automations.`} 
            />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white pt-24 pb-16 lg:pt-36">
                
                {/* Hero Header */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 mb-16 text-center">
                    <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white mx-auto">
                        <Calculator className="h-3 w-3 text-slate-800" />
                        {__('tools.tools_directory') || 'Free Tools'}
                    </div>
                    <h1 className="gsap-fade-up text-4xl lg:text-6xl font-extrabold text-[#111111] tracking-tight mb-4">
                        {__('tools.fb_title')}
                    </h1>
                    <p className="gsap-fade-up text-lg text-[#666666] max-w-3xl mx-auto font-normal leading-relaxed">
                        {__('tools.fb_desc')}
                    </p>

                    {/* Currency Switcher */}
                    <div className="gsap-fade-up mt-8 flex justify-center">
                        <div className="inline-flex items-center p-1 bg-slate-100 rounded-lg border border-slate-200/60">
                            <button
                                onClick={() => setIsEgp(false)}
                                className={`px-4 py-1.5 rounded-md text-xs font-bold transition-all ${
                                    !isEgp ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                USD ($)
                            </button>
                            <button
                                onClick={() => setIsEgp(true)}
                                className={`px-4 py-1.5 rounded-md text-xs font-bold transition-all ${
                                    isEgp ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                EGP (ج.م)
                            </button>
                        </div>
                    </div>
                </section>

                {/* Main Calculator Layout */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-8 mb-20">
                    
                    {/* Inputs Panel (Left) */}
                    <div className="lg:col-span-7 space-y-6">
                        
                        <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all">
                            <CardContent className="pt-6 space-y-8">
                                
                                {/* Followers Slider */}
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center">
                                        <Label htmlFor="followers" className="font-bold text-sm text-slate-700">
                                            {__('tools.fb_followers')}
                                        </Label>
                                        <span className="text-sm font-extrabold text-slate-900 px-2.5 py-1 bg-slate-100 rounded-md">
                                            {followers >= 500000 ? '500,000+' : followers.toLocaleString()}
                                        </span>
                                    </div>
                                    <input
                                        id="followers"
                                        type="range"
                                        min="1000"
                                        max="500000"
                                        step="1000"
                                        value={followers}
                                        onChange={(e) => setFollowers(parseInt(e.target.value))}
                                        className="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-900"
                                    />
                                    <div className="flex justify-between text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                                        <span>1K</span>
                                        <span>50K</span>
                                        <span>100K</span>
                                        <span>250K</span>
                                        <span>500K+</span>
                                    </div>
                                </div>

                                {/* Posts per Week Slider */}
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center">
                                        <Label htmlFor="posts" className="font-bold text-sm text-slate-700">
                                            {__('tools.fb_posts')}
                                        </Label>
                                        <span className="text-sm font-extrabold text-slate-900 px-2.5 py-1 bg-slate-100 rounded-md">
                                            {postsPerWeek} {postsPerWeek === 1 ? 'post' : 'posts'}
                                        </span>
                                    </div>
                                    <input
                                        id="posts"
                                        type="range"
                                        min="1"
                                        max="14"
                                        step="1"
                                        value={postsPerWeek}
                                        onChange={(e) => setPostsPerWeek(parseInt(e.target.value))}
                                        className="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-900"
                                    />
                                    <div className="flex justify-between text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                                        <span>1 post / week</span>
                                        <span>7 posts (daily)</span>
                                        <span>14 posts (twice daily)</span>
                                    </div>
                                </div>

                                {/* Chatbot Level selection */}
                                <div className="space-y-3">
                                    <Label className="font-bold text-sm text-slate-700 block">
                                        {__('tools.fb_chatbot')}
                                    </Label>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        {['none', 'basic', 'ai'].map((level) => {
                                            const active = chatbotLevel === level;
                                            return (
                                                <button
                                                    key={level}
                                                    type="button"
                                                    onClick={() => setChatbotLevel(level)}
                                                    className={`p-4 rounded-xl border text-left flex flex-col justify-between transition-all ${
                                                        active 
                                                            ? 'border-slate-900 bg-slate-950 text-white shadow-md' 
                                                            : 'border-slate-200 bg-white hover:border-slate-300 text-slate-800'
                                                    }`}
                                                >
                                                    <span className="font-extrabold text-xs tracking-wider uppercase mb-2">
                                                        {level === 'none' ? __('tools.fb_chatbot_none') : level === 'basic' ? __('tools.fb_chatbot_basic') : __('tools.fb_chatbot_ai')}
                                                    </span>
                                                    <span className={`text-[11px] font-normal leading-relaxed ${active ? 'text-slate-300' : 'text-slate-500'}`}>
                                                        {level === 'none' 
                                                            ? 'No automated message replies.' 
                                                            : level === 'basic' 
                                                            ? 'Standard automated FAQ triggers and auto-comments.'
                                                            : 'Advanced AI agent that captures leads and holds real natural chats.'}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Ad campaign option */}
                                <div className="space-y-3">
                                    <Label className="font-bold text-sm text-slate-700 block">
                                        {__('tools.fb_ads')}
                                    </Label>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        {[false, true].map((val) => {
                                            const active = manageAds === val;
                                            return (
                                                <button
                                                    key={val.toString()}
                                                    type="button"
                                                    onClick={() => setChatbotLevel(chatbotLevel)} // dummy wrapper logic
                                                    onClickCapture={() => setManageAds(val)}
                                                    className={`p-4 rounded-xl border text-left flex flex-col transition-all ${
                                                        active 
                                                            ? 'border-slate-900 bg-slate-950 text-white shadow-md' 
                                                            : 'border-slate-200 bg-white hover:border-slate-300 text-slate-800'
                                                    }`}
                                                >
                                                    <span className="font-extrabold text-xs tracking-wider uppercase mb-1">
                                                        {val === false ? __('tools.fb_ads_none') : __('tools.fb_ads_managed')}
                                                    </span>
                                                    <span className={`text-[11px] font-normal leading-relaxed ${active ? 'text-slate-300' : 'text-slate-500'}`}>
                                                        {val === false 
                                                            ? 'Exclude paid campaigns and audience targeting setup.' 
                                                            : 'Continuous budgeting, campaign metrics optimization, and pixel configuration.'}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                            </CardContent>
                        </Card>
                    </div>

                    {/* Results / Breakdown Panel (Right) */}
                    <div className="lg:col-span-5 space-y-6">
                        <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all flex flex-col justify-between h-full">
                            <CardHeader className="border-b border-slate-100 bg-slate-50/50">
                                <CardTitle className="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                                    <Calculator className="h-5 w-5 text-slate-600" />
                                    Estimation Results
                                </CardTitle>
                                <CardDescription>Estimated cost breakdown per month.</CardDescription>
                            </CardHeader>
                            <CardContent className="pt-6 space-y-4 flex-1">
                                
                                {/* Cost Display */}
                                <div className="text-center py-6 bg-slate-50 border border-slate-100 rounded-2xl mb-6">
                                    <span className="text-xs font-bold text-slate-500 uppercase tracking-widest block mb-2">
                                        {__('tools.fb_monthly_cost')}
                                    </span>
                                    <span className="text-4xl lg:text-5xl font-black text-slate-950 tracking-tight block">
                                        {formatCurrency(prices.total)}
                                    </span>
                                    {isEgp && (
                                        <span className="text-[10px] text-slate-400 font-semibold block mt-1">
                                            (Equivalent to USD ${prices.total})
                                        </span>
                                    )}
                                </div>

                                {/* Detailed list */}
                                <div className="space-y-3 text-xs sm:text-sm">
                                    
                                    <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                        <span className="text-slate-600 font-medium flex items-center gap-1.5">
                                            <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            {__('tools.fb_breakdown_base')}
                                        </span>
                                        <span className="font-bold text-slate-900">{formatCurrency(prices.base)}</span>
                                    </div>

                                    <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                        <span className="text-slate-600 font-medium flex items-center gap-1.5">
                                            <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            {__('tools.fb_breakdown_posts')}
                                        </span>
                                        <span className="font-bold text-slate-900">{formatCurrency(prices.posts)}</span>
                                    </div>

                                    {prices.chatbot > 0 && (
                                        <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                            <span className="text-slate-600 font-medium flex items-center gap-1.5">
                                                <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                {__('tools.fb_breakdown_chatbot')}
                                            </span>
                                            <span className="font-bold text-slate-900">{formatCurrency(prices.chatbot)}</span>
                                        </div>
                                    )}

                                    {prices.ads > 0 && (
                                        <div className="flex justify-between items-center border-b border-slate-100 pb-2">
                                            <span className="text-slate-600 font-medium flex items-center gap-1.5">
                                                <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                {__('tools.fb_breakdown_ads')}
                                            </span>
                                            <span className="font-bold text-slate-900">{formatCurrency(prices.ads)}</span>
                                        </div>
                                    )}

                                </div>
                            </CardContent>
                            <CardFooter className="flex-col border-t border-slate-100 pt-4 bg-slate-55/40 space-y-4">
                                <div className="flex gap-2 text-[11px] text-slate-500 font-normal leading-relaxed">
                                    <Info className="h-4 w-4 shrink-0 text-slate-400" />
                                    <span>{__('tools.fb_note')}</span>
                                </div>
                            </CardFooter>
                        </Card>
                    </div>
                </section>

                {/* Automation Funnel Box */}
                <section className="reveal-section max-w-4xl mx-auto px-6 lg:px-8">
                    <div className="gsap-fade-up bg-slate-900 rounded-3xl p-8 lg:p-12 text-white border border-slate-800 relative overflow-hidden shadow-2xl">
                        
                        <div className="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                            <Sparkles className="h-36 w-36" />
                        </div>
                        
                        <div className="relative z-10 max-w-2xl">
                            <h2 className="text-2xl lg:text-3xl font-extrabold mb-4 tracking-tight leading-tight">
                                {__('tools.fb_cta_title')}
                            </h2>
                            <p className="text-slate-300 text-sm lg:text-base mb-8 leading-relaxed font-normal">
                                {__('tools.fb_cta_desc')}
                            </p>

                            <form onSubmit={handleScopingSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="scopingPrompt" className="text-slate-300 font-semibold text-xs uppercase tracking-wider flex items-center gap-1">
                                        <MessageSquare className="h-3 w-3 text-slate-400" />
                                        Describe what custom page automations or workflows you need:
                                    </Label>
                                    <div className="flex flex-col sm:flex-row gap-3">
                                        <Input
                                            id="scopingPrompt"
                                            value={scopingPrompt}
                                            onChange={(e) => setScopingPrompt(e.target.value)}
                                            placeholder="e.g. Automate scheduling and reply to all post comments with our product link..."
                                            className="bg-slate-800/80 border-slate-700 text-white placeholder-slate-500 focus-visible:ring-white h-11 flex-1"
                                        />
                                        <Button
                                            type="submit"
                                            className="bg-white hover:bg-slate-100 text-slate-950 font-bold h-11 px-6 shrink-0"
                                        >
                                            {__('tools.fb_cta_btn')}
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

// Simple internal Input override to prevent conflicts
function Input({ className, ...props }) {
    return (
        <input
            {...props}
            className={`flex h-10 w-full rounded-md border border-slate-200 bg-transparent px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 disabled:cursor-not-allowed disabled:opacity-50 ${className}`}
        />
    );
}
