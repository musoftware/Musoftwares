import { useState, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Copy, MapPin, Globe, ArrowRightLeft, Check, Sparkles, MessageSquare } from 'lucide-react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

gsap.registerPlugin(ScrollTrigger);

export default function GpsConverter() {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";
    const [scopingPrompt, setScopingPrompt] = useState('');

    // --- State for DD -> DMS Conversion ---
    const [ddLat, setDdLat] = useState('30.0444');
    const [ddLng, setDdLng] = useState('31.2357');
    const [dmsResult, setDmsResult] = useState(null);

    // --- State for DMS -> DD Conversion ---
    const [dmsLatDeg, setDmsLatDeg] = useState('30');
    const [dmsLatMin, setDmsLatMin] = useState('2');
    const [dmsLatSec, setDmsLatSec] = useState('39.84');
    const [dmsLatDir, setDmsLatDir] = useState('N');

    const [dmsLngDeg, setDmsLngDeg] = useState('31');
    const [dmsLngMin, setDmsLngMin] = useState('14');
    const [dmsLngSec, setDmsLngSec] = useState('8.52');
    const [dmsLngDir, setDmsLngDir] = useState('E');

    const [ddResult, setDdResult] = useState(null);

    // Feedback states
    const [copiedDms, setCopiedDms] = useState(false);
    const [copiedDd, setCopiedDd] = useState(false);
    const [errorMsg, setErrorMsg] = useState('');

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

    // --- Conversion Logic DD to DMS ---
    const handleDdToDms = (e) => {
        e.preventDefault();
        setErrorMsg('');
        const lat = parseFloat(ddLat);
        const lng = parseFloat(ddLng);

        if (isNaN(lat) || lat < -90 || lat > 90 || isNaN(lng) || lng < -180 || lng > 180) {
            setErrorMsg(__('tools.gps_invalid') || 'Invalid latitude or longitude values.');
            setDmsResult(null);
            return;
        }

        const convertCoord = (val, isLat) => {
            const dir = isLat 
                ? (val >= 0 ? 'N' : 'S') 
                : (val >= 0 ? 'E' : 'W');
            const absVal = Math.abs(val);
            const deg = Math.floor(absVal);
            const minFull = (absVal - deg) * 60;
            const min = Math.floor(minFull);
            const sec = parseFloat(((minFull - min) * 60).toFixed(4));
            
            return {
                deg,
                min,
                sec,
                dir,
                formatted: `${deg}° ${min}' ${sec}" ${dir}`
            };
        };

        const latDms = convertCoord(lat, true);
        const lngDms = convertCoord(lng, false);

        setDmsResult({
            lat: latDms,
            lng: lngDms,
            formatted: `${latDms.formatted}, ${lngDms.formatted}`
        });
    };

    // --- Conversion Logic DMS to DD ---
    const handleDmsToDd = (e) => {
        e.preventDefault();
        setErrorMsg('');

        const latDeg = parseFloat(dmsLatDeg);
        const latMin = parseFloat(dmsLatMin);
        const latSec = parseFloat(dmsLatSec);

        const lngDeg = parseFloat(dmsLngDeg);
        const lngMin = parseFloat(dmsLngMin);
        const lngSec = parseFloat(dmsLngSec);

        if (
            isNaN(latDeg) || latDeg < 0 || latDeg > 90 ||
            isNaN(latMin) || latMin < 0 || latMin >= 60 ||
            isNaN(latSec) || latSec < 0 || latSec >= 60 ||
            isNaN(lngDeg) || lngDeg < 0 || lngDeg > 180 ||
            isNaN(lngMin) || lngMin < 0 || lngMin >= 60 ||
            isNaN(lngSec) || lngSec < 0 || lngSec >= 60
        ) {
            setErrorMsg(__('tools.gps_invalid') || 'Invalid latitude or longitude values.');
            setDdResult(null);
            return;
        }

        const calcDd = (deg, min, sec, dir) => {
            let dd = deg + (min / 60) + (sec / 3600);
            if (dir === 'S' || dir === 'W') {
                dd = -dd;
            }
            return parseFloat(dd.toFixed(7));
        };

        const latDd = calcDd(latDeg, latMin, latSec, dmsLatDir);
        const lngDd = calcDd(lngDeg, lngMin, lngSec, dmsLngDir);

        setDdResult({
            lat: latDd,
            lng: lngDd,
            formatted: `${latDd}, ${lngDd}`
        });
    };

    const handleCopy = (text, type) => {
        navigator.clipboard.writeText(text);
        if (type === 'dms') {
            setCopiedDms(true);
            setTimeout(() => setCopiedDms(false), 2000);
        } else {
            setCopiedDd(true);
            setTimeout(() => setCopiedDd(false), 2000);
        }
    };

    const handleScopingSubmit = (e) => {
        e.preventDefault();
        if (!scopingPrompt.trim()) return;
        router.visit(`/register?prefill_desc=${encodeURIComponent(scopingPrompt.trim())}`);
    };

    const getGoogleMapsLink = (lat, lng) => {
        return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
    };

    // JSON-LD structured data for Google Search Indexing
    const jsonLd = {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": __('tools.gps_title'),
        "description": __('tools.gps_desc'),
        "applicationCategory": "DeveloperApplication",
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
                title={`${__('tools.gps_title')} | Musoftware`}
                description={__('tools.gps_desc')}
                jsonLd={jsonLd}
            />

            <FloatingWhatsAppButton 
                phoneNumber={phoneNumber} 
                defaultMessage="Hello Mahmoud, I'm interested in building a custom GIS or map tracking system." 
            />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white pt-24 pb-16 lg:pt-36">
                
                {/* Hero Header */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 mb-16 text-center">
                    <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white mx-auto">
                        <Globe className="h-3 w-3 text-slate-800" />
                        {__('tools.tools_directory') || 'Free Tools'}
                    </div>
                    <h1 className="gsap-fade-up text-4xl lg:text-6xl font-extrabold text-[#111111] tracking-tight mb-4">
                        {__('tools.gps_title')}
                    </h1>
                    <p className="gsap-fade-up text-lg text-[#666666] max-w-3xl mx-auto font-normal leading-relaxed">
                        {__('tools.gps_desc')}
                    </p>
                </section>

                {/* Main Converter Layout */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-8 mb-20">
                    
                    {/* DD to DMS Card */}
                    <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all flex flex-col justify-between">
                        <CardHeader>
                            <CardTitle className="text-xl font-bold flex items-center gap-2 text-slate-900">
                                <ArrowRightLeft className="h-5 w-5 text-slate-500" />
                                {__('tools.gps_decimal_degrees')} &rarr; {__('tools.gps_dms')}
                            </CardTitle>
                            <CardDescription>
                                Convert decimals (e.g. 30.0444) to degrees, minutes, and seconds.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="ddLat">{__('tools.gps_lat')}</Label>
                                    <Input 
                                        id="ddLat" 
                                        type="number" 
                                        step="any"
                                        value={ddLat} 
                                        onChange={(e) => setDdLat(e.target.value)} 
                                        className="border-slate-200 focus-visible:ring-slate-900"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ddLng">{__('tools.gps_lng')}</Label>
                                    <Input 
                                        id="ddLng" 
                                        type="number" 
                                        step="any"
                                        value={ddLng} 
                                        onChange={(e) => setDdLng(e.target.value)} 
                                        className="border-slate-200 focus-visible:ring-slate-900"
                                    />
                                </div>
                            </div>

                            {dmsResult && (
                                <div className="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100 flex flex-col gap-2">
                                    <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider">Result (DMS)</span>
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="font-mono text-sm sm:text-base font-bold text-slate-900 break-all select-all">
                                            {dmsResult.formatted}
                                        </span>
                                        <div className="flex items-center gap-1.5 shrink-0">
                                            <Button 
                                                variant="outline" 
                                                size="icon" 
                                                onClick={() => handleCopy(dmsResult.formatted, 'dms')}
                                                className="h-8 w-8 hover:bg-slate-100 border-slate-200"
                                            >
                                                {copiedDms ? <Check className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
                                            </Button>
                                            <a 
                                                href={getGoogleMapsLink(ddLat, ddLng)} 
                                                target="_blank" 
                                                rel="noreferrer"
                                                className="inline-flex h-8 items-center gap-1 px-2.5 rounded-md border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition-colors"
                                            >
                                                <MapPin className="h-3 w-3" />
                                                <span className="hidden sm:inline">{__('tools.gps_view_maps')}</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                        <CardFooter className="pt-2">
                            <Button 
                                onClick={handleDdToDms} 
                                className="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold"
                            >
                                {__('tools.gps_convert_to_dms')}
                            </Button>
                        </CardFooter>
                    </Card>

                    {/* DMS to DD Card */}
                    <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all flex flex-col justify-between">
                        <CardHeader>
                            <CardTitle className="text-xl font-bold flex items-center gap-2 text-slate-900">
                                <ArrowRightLeft className="h-5 w-5 text-slate-500" />
                                {__('tools.gps_dms')} &rarr; {__('tools.gps_decimal_degrees')}
                            </CardTitle>
                            <CardDescription>
                                Convert degrees, minutes, and seconds coordinates to decimal format.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Latitude inputs */}
                            <div className="space-y-2">
                                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider block">{__('tools.gps_lat')}</span>
                                <div className="grid grid-cols-4 gap-2">
                                    <div className="space-y-1">
                                        <Label htmlFor="latDeg" className="text-[10px] text-slate-500">{__('tools.gps_degrees')}</Label>
                                        <Input id="latDeg" type="number" value={dmsLatDeg} onChange={(e) => setDmsLatDeg(e.target.value)} className="h-9 px-2 text-center" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="latMin" className="text-[10px] text-slate-500">{__('tools.gps_minutes')}</Label>
                                        <Input id="latMin" type="number" value={dmsLatMin} onChange={(e) => setDmsLatMin(e.target.value)} className="h-9 px-2 text-center" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="latSec" className="text-[10px] text-slate-500">{__('tools.gps_seconds')}</Label>
                                        <Input id="latSec" type="number" step="any" value={dmsLatSec} onChange={(e) => setDmsLatSec(e.target.value)} className="h-9 px-2 text-center" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label className="text-[10px] text-slate-500">{__('tools.gps_direction')}</Label>
                                        <Select value={dmsLatDir} onValueChange={setDmsLatDir}>
                                            <SelectTrigger className="h-9">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="N">N</SelectItem>
                                                <SelectItem value="S">S</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>

                            {/* Longitude inputs */}
                            <div className="space-y-2">
                                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider block">{__('tools.gps_lng')}</span>
                                <div className="grid grid-cols-4 gap-2">
                                    <div className="space-y-1">
                                        <Label htmlFor="lngDeg" className="text-[10px] text-slate-500">{__('tools.gps_degrees')}</Label>
                                        <Input id="lngDeg" type="number" value={dmsLngDeg} onChange={(e) => setDmsLngDeg(e.target.value)} className="h-9 px-2 text-center" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="lngMin" className="text-[10px] text-slate-500">{__('tools.gps_minutes')}</Label>
                                        <Input id="lngMin" type="number" value={dmsLngMin} onChange={(e) => setDmsLngMin(e.target.value)} className="h-9 px-2 text-center" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="lngSec" className="text-[10px] text-slate-500">{__('tools.gps_seconds')}</Label>
                                        <Input id="lngSec" type="number" step="any" value={dmsLngSec} onChange={(e) => setDmsLngSec(e.target.value)} className="h-9 px-2 text-center" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label className="text-[10px] text-slate-500">{__('tools.gps_direction')}</Label>
                                        <Select value={dmsLngDir} onValueChange={setDmsLngDir}>
                                            <SelectTrigger className="h-9">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="E">E</SelectItem>
                                                <SelectItem value="W">W</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>

                            {ddResult && (
                                <div className="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100 flex flex-col gap-2">
                                    <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider">Result (Decimal)</span>
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="font-mono text-sm sm:text-base font-bold text-slate-900 break-all select-all">
                                            {ddResult.formatted}
                                        </span>
                                        <div className="flex items-center gap-1.5 shrink-0">
                                            <Button 
                                                variant="outline" 
                                                size="icon" 
                                                onClick={() => handleCopy(ddResult.formatted, 'dd')}
                                                className="h-8 w-8 hover:bg-slate-100 border-slate-200"
                                            >
                                                {copiedDd ? <Check className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
                                            </Button>
                                            <a 
                                                href={getGoogleMapsLink(ddResult.lat, ddResult.lng)} 
                                                target="_blank" 
                                                rel="noreferrer"
                                                className="inline-flex h-8 items-center gap-1 px-2.5 rounded-md border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition-colors"
                                            >
                                                <MapPin className="h-3 w-3" />
                                                <span className="hidden sm:inline">{__('tools.gps_view_maps')}</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                        <CardFooter className="pt-2">
                            <Button 
                                onClick={handleDmsToDd} 
                                className="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold"
                            >
                                {__('tools.gps_convert_to_dd')}
                            </Button>
                        </CardFooter>
                    </Card>
                </section>

                {errorMsg && (
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 mb-8">
                        <div className="p-4 bg-red-50 border border-red-200 text-red-700 text-sm font-semibold rounded-xl text-center">
                            {errorMsg}
                        </div>
                    </div>
                )}

                {/* Conversion Funnel Box */}
                <section className="reveal-section max-w-4xl mx-auto px-6 lg:px-8">
                    <div className="gsap-fade-up bg-slate-900 rounded-3xl p-8 lg:p-12 text-white border border-slate-800 relative overflow-hidden shadow-2xl">
                        
                        <div className="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                            <Sparkles className="h-36 w-36" />
                        </div>
                        
                        <div className="relative z-10 max-w-2xl">
                            <h2 className="text-2xl lg:text-3xl font-extrabold mb-4 tracking-tight leading-tight">
                                {__('tools.gps_cta_title')}
                            </h2>
                            <p className="text-slate-300 text-sm lg:text-base mb-8 leading-relaxed font-normal">
                                {__('tools.gps_cta_desc')}
                            </p>

                            <form onSubmit={handleScopingSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="scopingPrompt" className="text-slate-300 font-semibold text-xs uppercase tracking-wider flex items-center gap-1">
                                        <MessageSquare className="h-3 w-3 text-slate-400" />
                                        Describe what maps/systems you need built:
                                    </Label>
                                    <div className="flex flex-col sm:flex-row gap-3">
                                        <Input
                                            id="scopingPrompt"
                                            value={scopingPrompt}
                                            onChange={(e) => setScopingPrompt(e.target.value)}
                                            placeholder="e.g. A map dashboard to show field employee locations in real time..."
                                            className="bg-slate-800/80 border-slate-700 text-white placeholder-slate-500 focus-visible:ring-white h-11 flex-1"
                                        />
                                        <Button
                                            type="submit"
                                            className="bg-white hover:bg-slate-100 text-slate-950 font-bold h-11 px-6 shrink-0"
                                        >
                                            {__('tools.gps_cta_btn')}
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
