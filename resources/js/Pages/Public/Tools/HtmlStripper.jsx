import { useState, useRef, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { SeoHead } from '@/Components/ui/SeoHead';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { Terminal, Copy, Check, FileCode2, Sparkles, MessageSquare, ShieldCheck } from 'lucide-react';
import { __ } from '@/lib/i18n';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';

gsap.registerPlugin(ScrollTrigger);

export default function HtmlStripper() {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";
    const [scopingPrompt, setScopingPrompt] = useState('');

    const [htmlInput, setHtmlInput] = useState('');
    const [stripMode, setStripMode] = useState('all'); // all, styling, basic
    const [compressWhitespace, setCompressWhitespace] = useState(false);
    const [cleanText, setCleanText] = useState('');
    const [copied, setCopied] = useState(false);

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

    // --- HTML Stripper Logic ---
    useEffect(() => {
        if (!htmlInput.trim()) {
            setCleanText('');
            return;
        }

        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlInput, 'text/html');

            // 1. Remove scripts and styles in all modes
            doc.querySelectorAll('script, style, iframe, object, embed').forEach(el => el.remove());

            let resultText = '';
            if (stripMode === 'all') {
                // Get raw text content only
                resultText = doc.body.textContent || doc.body.innerText || '';
            } else if (stripMode === 'styling') {
                // Keep tags, but strip classes, styling, ids, and JS event attributes
                const cleanAttributes = (node) => {
                    if (node.nodeType === 1) { // Element Node
                        const attrs = Array.from(node.attributes);
                        attrs.forEach(attr => {
                            // Remove event handlers (e.g. onclick) and styles/classes
                            if (attr.name.startsWith('on') || ['style', 'class', 'id'].includes(attr.name)) {
                                node.removeAttribute(attr.name);
                            }
                        });
                        Array.from(node.childNodes).forEach(cleanAttributes);
                    }
                };
                cleanAttributes(doc.body);
                resultText = doc.body.innerHTML.trim();
            } else if (stripMode === 'basic') {
                // Keep only a, b, i, strong, p, br tags. Strip out all other element tags but keep contents.
                const allowed = ['a', 'b', 'i', 'strong', 'p', 'br'];
                
                const sanitizeNode = (node) => {
                    if (node.nodeType === 1) { // Element Node
                        const tagName = node.tagName.toLowerCase();
                        
                        // Clean node attributes
                        const attrs = Array.from(node.attributes);
                        attrs.forEach(attr => {
                            if (tagName === 'a' && attr.name === 'href') {
                                // Keep links href
                            } else {
                                node.removeAttribute(attr.name);
                            }
                        });

                        // Recurse children first
                        Array.from(node.childNodes).forEach(sanitizeNode);

                        if (!allowed.includes(tagName) && tagName !== 'body' && tagName !== 'html') {
                            // Replace element tag with its children
                            const parent = node.parentNode;
                            if (parent) {
                                while (node.firstChild) {
                                    parent.insertBefore(node.firstChild, node);
                                }
                                parent.removeChild(node);
                            }
                        }
                    }
                };
                sanitizeNode(doc.body);
                resultText = doc.body.innerHTML.trim();
            }

            if (compressWhitespace) {
                // Compress consecutive whitespaces and double line breaks
                resultText = resultText.replace(/\n\s*\n/g, '\n').replace(/[ \t]+/g, ' ').trim();
            }
            setCleanText(resultText);
        } catch (e) {
            setCleanText('Error parsing HTML code.');
        }
    }, [htmlInput, stripMode, compressWhitespace]);

    const handleCopy = () => {
        if (!cleanText) return;
        navigator.clipboard.writeText(cleanText);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleScopingSubmit = (e) => {
        e.preventDefault();
        if (!scopingPrompt.trim()) return;

        const finalPrompt = `Client requirements details about Data Extraction/Web Scraping/API:
${scopingPrompt}

Context:
- User was using the HTML Tag Stripper tool.`;

        router.visit(`/register?prefill_desc=${encodeURIComponent(finalPrompt.trim())}`);
    };

    // JSON-LD structured data for Google Search Indexing
    const jsonLd = {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": __('tools.html_title'),
        "description": __('tools.html_desc'),
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
                title={`${__('tools.html_title')} | Musoftware`}
                description={__('tools.html_desc')}
                jsonLd={jsonLd}
            />

            <FloatingWhatsAppButton 
                phoneNumber={phoneNumber} 
                defaultMessage="Hello Mahmoud, I have a question about custom web scraping or API integrations." 
            />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white pt-24 pb-16 lg:pt-36">
                
                {/* Hero Header */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 mb-16 text-center">
                    <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-6 bg-white mx-auto">
                        <Terminal className="h-3 w-3 text-slate-800" />
                        {__('tools.tools_directory') || 'Free Tools'}
                    </div>
                    <h1 className="gsap-fade-up text-4xl lg:text-6xl font-extrabold text-[#111111] tracking-tight mb-4">
                        {__('tools.html_title')}
                    </h1>
                    <p className="gsap-fade-up text-lg text-[#666666] max-w-3xl mx-auto font-normal leading-relaxed">
                        {__('tools.html_desc')}
                    </p>
                </section>

                {/* Main Converter Layout */}
                <section className="reveal-section max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-8 mb-20">
                    
                    {/* Inputs panel */}
                    <div className="space-y-6">
                        <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all flex flex-col h-full justify-between">
                            <CardHeader>
                                <CardTitle className="text-xl font-bold flex items-center gap-2 text-slate-900">
                                    <FileCode2 className="h-5 w-5 text-slate-500" />
                                    {__('tools.html_input_label')}
                                </CardTitle>
                                <CardDescription>Paste your raw HTML here to filter code structures.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6 flex-1">
                                
                                {/* Mode Selector */}
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    {[
                                        { id: 'all', label: __('tools.html_strip_all') },
                                        { id: 'styling', label: __('tools.html_strip_styling') },
                                        { id: 'basic', label: __('tools.html_keep_basic') }
                                    ].map((mode) => {
                                        const active = stripMode === mode.id;
                                        return (
                                            <button
                                                key={mode.id}
                                                type="button"
                                                onClick={() => setStripMode(mode.id)}
                                                className={`px-3 py-2 text-xs font-bold rounded-lg border text-center transition-all ${
                                                    active 
                                                        ? 'bg-slate-950 text-white border-slate-950 shadow-sm' 
                                                        : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
                                                }`}
                                            >
                                                {mode.label}
                                            </button>
                                        );
                                    })}
                                </div>

                                <div className="flex items-center space-x-2 rtl:space-x-reverse py-1">
                                    <input
                                        id="compressWs"
                                        type="checkbox"
                                        checked={compressWhitespace}
                                        onChange={(e) => setCompressWhitespace(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 accent-slate-900 cursor-pointer"
                                    />
                                    <Label htmlFor="compressWs" className="text-xs font-semibold text-slate-600 cursor-pointer">
                                        Compress Whitespace (Remove extra blank lines)
                                    </Label>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="htmlInput" className="sr-only">HTML Source</Label>
                                    <Textarea
                                        id="htmlInput"
                                        value={htmlInput}
                                        onChange={(e) => setHtmlInput(e.target.value)}
                                        placeholder={__('tools.html_placeholder') || 'Paste HTML code here...'}
                                        rows={12}
                                        className="font-mono text-xs border-slate-200 focus-visible:ring-slate-900 bg-slate-50/50 resize-none h-[300px]"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Outputs panel */}
                    <div className="space-y-6">
                        <Card className="gsap-fade-up border-[#e5e5e5] bg-white shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-slate-300 transition-all flex flex-col h-full justify-between">
                            <CardHeader className="border-b border-slate-100/80 bg-slate-50/20">
                                <CardTitle className="text-xl font-bold flex items-center justify-between gap-2 text-slate-900">
                                    <span className="flex items-center gap-2">
                                        <ShieldCheck className="h-5 w-5 text-slate-500" />
                                        {__('tools.html_output_label')}
                                    </span>
                                    {cleanText && (
                                        <Button 
                                            onClick={handleCopy} 
                                            variant="outline" 
                                            size="sm" 
                                            className="h-8 gap-1 border-slate-200"
                                        >
                                            {copied ? (
                                                <>
                                                    <Check className="h-3 w-3 text-green-600" />
                                                    <span className="text-xs text-green-600 font-bold">{__('tools.html_copied')}</span>
                                                </>
                                            ) : (
                                                <>
                                                    <Copy className="h-3 w-3" />
                                                    <span className="text-xs font-bold">{__('tools.html_copy_btn')}</span>
                                                </>
                                            )}
                                        </Button>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-6 flex-1">
                                <Textarea
                                    value={cleanText}
                                    readOnly
                                    placeholder="Resulting clean text will automatically appear here..."
                                    rows={15}
                                    className="font-mono text-xs border-slate-150 focus-visible:ring-slate-900 bg-slate-50/30 resize-none h-[340px]"
                                />
                            </CardContent>
                        </Card>
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
                                {__('tools.html_cta_title')}
                            </h2>
                            <p className="text-slate-300 text-sm lg:text-base mb-8 leading-relaxed font-normal">
                                {__('tools.html_cta_desc')}
                            </p>

                            <form onSubmit={handleScopingSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="scopingPrompt" className="text-slate-300 font-semibold text-xs uppercase tracking-wider flex items-center gap-1">
                                        <MessageSquare className="h-3 w-3 text-slate-400" />
                                        Describe your custom scraping or API integration requirements:
                                    </Label>
                                    <div className="flex flex-col sm:flex-row gap-3">
                                        <Input
                                            id="scopingPrompt"
                                            value={scopingPrompt}
                                            onChange={(e) => setScopingPrompt(e.target.value)}
                                            placeholder="e.g. Scrape products from an e-commerce catalog and export them to Shopify..."
                                            className="bg-slate-800/80 border-slate-700 text-white placeholder-slate-500 focus-visible:ring-white h-11 flex-1"
                                        />
                                        <Button
                                            type="submit"
                                            className="bg-white hover:bg-slate-100 text-slate-950 font-bold h-11 px-6 shrink-0"
                                        >
                                            {__('tools.html_cta_btn')}
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
