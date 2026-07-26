import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Button } from '@/components/ui/button';
import { motion } from 'framer-motion';
import { useState, useEffect } from 'react';
import { usePWAInstall } from '@/hooks/usePWAInstall';
import { Download, X } from 'lucide-react';

// Schema Data
const schemaData = {
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://musoftwares.com/#organization",
      "name": "Musoftwares",
      "url": "https://musoftwares.com",
      "logo": "https://musoftwares.com/logo.png",
      "description": "Musoftwares provides enterprise ERP systems, WhatsApp Business Verification, and Meta API integration services to help businesses scale globally.",
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is WhatsApp Business Verification?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "WhatsApp Business Verification is the process of verifying your business with Meta to access advanced API features, increase trust, and reduce account restrictions. The process typically requires official business documentation."
          }
        },
        {
          "@type": "Question",
          "name": "How long does the ERP implementation take?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Depending on the complexity, standard ERP implementations take between 2 to 4 weeks, including team training and data migration."
          }
        },
        {
          "@type": "Question",
          "name": "Can I verify without a company website?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Meta strongly prefers a functioning company website with the same legal domain name. However, alternative proofs like utility bills and tax documents can sometimes suffice depending on the region."
          }
        }
      ]
    }
  ]
};

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    const { isInstallable, isStandalone, install } = usePWAInstall();
    const [showBanner, setShowBanner] = useState(true);
    const [isIOS, setIsIOS] = useState(false);

    useEffect(() => {
        const checkIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !(window as any).MSStream;
        setIsIOS(checkIOS);
    }, []);

    return (
        <>
            <Head>
                <title>{__('general.welcome') || 'Welcome to Musoftwares | Comprehensive Business Platform'}</title>
                <meta name="description" content="Musoftwares is the ultimate comprehensive business platform integrating ERP, CRM, Billing, and Marketplace features. We specialize in Meta API Integration, WhatsApp Business Verification, and custom ERP development to help businesses automate operations and scale efficiently." />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaData) }} />
            </Head>
            
            <div className="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-zinc-900 selection:text-white dark:selection:bg-zinc-100 dark:selection:text-zinc-900 font-sans flex flex-col relative overflow-hidden">
                
                {/* Background Decoration */}
                <div className="absolute inset-0 pointer-events-none overflow-hidden flex justify-center items-center">
                    <div className="absolute w-[800px] h-[800px] bg-zinc-200/50 dark:bg-zinc-800/20 rounded-full blur-3xl -top-1/4 -right-1/4 opacity-50 mix-blend-multiply dark:mix-blend-screen"></div>
                    <div className="absolute w-[600px] h-[600px] bg-zinc-300/30 dark:bg-zinc-800/30 rounded-full blur-3xl -bottom-1/4 -left-1/4 opacity-50 mix-blend-multiply dark:mix-blend-screen"></div>
                </div>

                <div className="relative z-10 flex flex-col min-h-screen">
                    <header className="container mx-auto px-6 py-8 flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                            <div className="w-8 h-8 bg-zinc-900 dark:bg-zinc-100 rounded-lg flex items-center justify-center">
                                <span className="text-white dark:text-zinc-900 font-bold text-xl">M</span>
                            </div>
                            <span className="font-bold text-xl tracking-tight">Musoftwares</span>
                        </div>
                        
                        <nav className="hidden md:flex items-center space-x-6 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                            <a href="#services" className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Services</a>
                            <a href="#case-studies" className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Case Studies</a>
                            <a href="#faq" className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">FAQ</a>
                            <a href="/docs" className="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Docs</a>
                        </nav>

                        <div className="flex items-center space-x-4">
                            {auth.user ? (
                                <Link href={route('dashboard')}>
                                    <Button variant="outline" className="rounded-full px-6 font-medium border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        {__('general.dashboard') || 'Dashboard'}
                                    </Button>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')} className="hidden sm:block text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                                        {__('general.log_in') || 'Log in'}
                                    </Link>
                                    <Link href={route('register')}>
                                        <Button className="rounded-full px-6 bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 font-medium">
                                            {__('general.register') || 'Get Started'}
                                        </Button>
                                    </Link>
                                </>
                            )}
                        </div>
                    </header>

                    {/* HERO SECTION */}
                    <main className="flex-1 flex flex-col items-center">
                        <section className="container mx-auto px-6 py-20 lg:py-32 text-center max-w-5xl">
                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                className="space-y-6"
                            >
                                <h1 className="text-5xl sm:text-6xl md:text-7xl font-extrabold tracking-tight leading-tight">
                                    The Comprehensive <br className="hidden sm:block" />
                                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-zinc-500 to-zinc-900 dark:from-zinc-400 dark:to-zinc-100">
                                        Business Platform
                                    </span>
                                </h1>
                                <p className="text-lg sm:text-xl text-zinc-600 dark:text-zinc-300 max-w-3xl mx-auto leading-relaxed text-left sm:text-center">
                                    <strong className="text-zinc-900 dark:text-white">Musoftwares</strong> is the ultimate comprehensive business platform integrating ERP, CRM, Billing, and Marketplace features. We specialize in Meta API Integration, WhatsApp Business Verification, and custom ERP development to help businesses automate operations and scale efficiently without technical bottlenecks.
                                </p>
                            </motion.div>

                            <motion.div 
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5, delay: 0.2 }}
                                className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4"
                            >
                                {auth.user ? (
                                    <Link href={route('dashboard')}>
                                        <Button size="lg" className="h-12 px-8 rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 text-base font-medium w-full sm:w-auto shadow-sm">
                                            Go to Dashboard
                                        </Button>
                                    </Link>
                                ) : (
                                    <>
                                        <Link href={route('register')}>
                                            <Button size="lg" className="h-12 px-8 rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 text-base font-medium w-full sm:w-auto shadow-sm">
                                                Start your free trial
                                            </Button>
                                        </Link>
                                        <a href="#services">
                                            <Button size="lg" variant="outline" className="h-12 px-8 rounded-full border-zinc-200 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-base font-medium w-full sm:w-auto">
                                                Explore Services
                                            </Button>
                                        </a>
                                    </>
                                )}
                            </motion.div>
                        </section>

                        {/* SERVICES SECTION */}
                        <section id="services" className="w-full bg-white dark:bg-zinc-900/50 py-24 border-y border-zinc-200 dark:border-zinc-800">
                            <div className="container mx-auto px-6">
                                <div className="text-center mb-16">
                                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">Dedicated Business Solutions</h2>
                                    <p className="text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                                        Discover our highly specialized services designed to solve specific operational challenges, from global communication to deep enterprise management.
                                    </p>
                                </div>

                                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                                    {/* Service 1 */}
                                    <a href="/services/whatsapp-business-verification" className="group block p-8 rounded-3xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 hover:shadow-lg transition-all duration-300">
                                        <div className="w-12 h-12 rounded-2xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-6 text-green-600 dark:text-green-400">
                                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <h3 className="text-xl font-semibold mb-3 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">WhatsApp Business Verification</h3>
                                        <p className="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed mb-6">
                                            Get your business officially verified with Meta. Gain access to the WhatsApp Cloud API, increase trust with customers, and mitigate account bans effectively.
                                        </p>
                                        <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100 flex items-center gap-1 group-hover:gap-2 transition-all">
                                            Learn more <span aria-hidden="true">&rarr;</span>
                                        </span>
                                    </a>

                                    {/* Service 2 */}
                                    <a href="/services/meta-api-integration" className="group block p-8 rounded-3xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 hover:shadow-lg transition-all duration-300">
                                        <div className="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-6 text-blue-600 dark:text-blue-400">
                                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <h3 className="text-xl font-semibold mb-3 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">Meta API Integration</h3>
                                        <p className="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed mb-6">
                                            Seamlessly integrate Facebook, Instagram, and WhatsApp Graph APIs into your existing systems to automate marketing and customer support at scale.
                                        </p>
                                        <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100 flex items-center gap-1 group-hover:gap-2 transition-all">
                                            Learn more <span aria-hidden="true">&rarr;</span>
                                        </span>
                                    </a>

                                    {/* Service 3 */}
                                    <a href="/services/erp-development" className="group block p-8 rounded-3xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 hover:shadow-lg transition-all duration-300">
                                        <div className="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-6 text-purple-600 dark:text-purple-400">
                                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <h3 className="text-xl font-semibold mb-3 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">Custom ERP Systems</h3>
                                        <p className="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed mb-6">
                                            We build and deploy robust Enterprise Resource Planning systems for laboratories, real estate, and B2B SaaS companies using advanced Laravel architecture.
                                        </p>
                                        <span className="text-sm font-medium text-zinc-900 dark:text-zinc-100 flex items-center gap-1 group-hover:gap-2 transition-all">
                                            Learn more <span aria-hidden="true">&rarr;</span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </section>

                        {/* CASE STUDIES & TESTIMONIALS */}
                        <section id="case-studies" className="w-full py-24">
                            <div className="container mx-auto px-6">
                                <div className="text-center mb-16">
                                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">Proven Results</h2>
                                    <p className="text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                                        Don't just take our word for it. See how our comprehensive solutions have transformed real businesses.
                                    </p>
                                </div>

                                <div className="grid md:grid-cols-2 gap-12 max-w-5xl mx-auto">
                                    {/* Testimonial */}
                                    <div className="flex flex-col justify-between p-8 rounded-3xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                                        <blockquote className="text-lg text-zinc-700 dark:text-zinc-300 mb-8">
                                            "We completed Meta Business Verification in exactly 7 days. Before Musoftwares, we were struggling with constant account restrictions. Now our WhatsApp API flow handles 10k+ messages daily flawlessly."
                                        </blockquote>
                                        <div className="flex items-center gap-4">
                                            <div className="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-800 overflow-hidden">
                                                <div className="w-full h-full bg-gradient-to-br from-zinc-300 to-zinc-400 dark:from-zinc-700 dark:to-zinc-800"></div>
                                            </div>
                                            <div>
                                                <div className="font-semibold text-zinc-900 dark:text-zinc-100">Ahmed M.</div>
                                                <div className="text-sm text-zinc-500 dark:text-zinc-400">CTO, Marketing Agency</div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Case Study Teaser */}
                                    <div className="flex flex-col justify-center items-start p-8 rounded-3xl bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900">
                                        <span className="px-3 py-1 text-xs font-semibold rounded-full bg-zinc-800 dark:bg-zinc-200 text-zinc-300 dark:text-zinc-700 mb-4">Case Study</span>
                                        <h3 className="text-2xl font-bold mb-3">Laboratory Management ERP</h3>
                                        <p className="text-zinc-400 dark:text-zinc-600 mb-8">
                                            See how we replaced 4 different legacy software tools with one unified ERP, saving the client 40 hours of manual data entry per week.
                                        </p>
                                        <a href="/case-studies/laboratory-erp">
                                            <Button className="rounded-full bg-white text-zinc-900 hover:bg-zinc-200 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">
                                                Read the full story
                                            </Button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {/* FAQ SECTION */}
                        <section id="faq" className="w-full bg-white dark:bg-zinc-900/50 py-24 border-t border-zinc-200 dark:border-zinc-800">
                            <div className="container mx-auto px-6 max-w-3xl">
                                <div className="text-center mb-16">
                                    <h2 className="text-3xl md:text-4xl font-bold tracking-tight mb-4">Frequently Asked Questions</h2>
                                </div>

                                <div className="space-y-6">
                                    <div className="border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 bg-zinc-50 dark:bg-zinc-950">
                                        <h3 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">What is WhatsApp Business Verification?</h3>
                                        <p className="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                            WhatsApp Business Verification is the process of verifying a business with Meta to access business features and increase trust. The process typically requires business documentation and an approved Meta Business account.
                                        </p>
                                    </div>
                                    
                                    <div className="border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 bg-zinc-50 dark:bg-zinc-950">
                                        <h3 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">How long does the ERP implementation take?</h3>
                                        <p className="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                            Depending on the complexity, standard ERP implementations take between 2 to 4 weeks, including team training and data migration. We prioritize minimal downtime during transitions.
                                        </p>
                                    </div>

                                    <div className="border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 bg-zinc-50 dark:bg-zinc-950">
                                        <h3 className="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Can I verify without a company website?</h3>
                                        <p className="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                            Meta strongly prefers a functioning company website. However, alternative proofs like utility bills and tax documents can sometimes suffice. We consult with you to build the strongest application possible.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </main>

                    <footer className="w-full bg-zinc-50 dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 py-12">
                        <div className="container mx-auto px-6">
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12 text-sm">
                                <div>
                                    <h4 className="font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Solutions</h4>
                                    <ul className="space-y-3 text-zinc-500 dark:text-zinc-400">
                                        <li><a href="/services/whatsapp-business-verification" className="hover:text-zinc-900 dark:hover:text-zinc-100">WhatsApp Verification</a></li>
                                        <li><a href="/services/meta-api-integration" className="hover:text-zinc-900 dark:hover:text-zinc-100">Meta API Integration</a></li>
                                        <li><a href="/services/erp-development" className="hover:text-zinc-900 dark:hover:text-zinc-100">Custom ERP Systems</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Comparisons</h4>
                                    <ul className="space-y-3 text-zinc-500 dark:text-zinc-400">
                                        <li><a href="/compare/laravel-vs-nodejs" className="hover:text-zinc-900 dark:hover:text-zinc-100">Laravel vs Node.js ERP</a></li>
                                        <li><a href="/compare/whatsapp-api-vs-twilio" className="hover:text-zinc-900 dark:hover:text-zinc-100">WhatsApp API vs Twilio</a></li>
                                        <li><a href="/alternatives/twilio" className="hover:text-zinc-900 dark:hover:text-zinc-100">Twilio Alternatives</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Resources</h4>
                                    <ul className="space-y-3 text-zinc-500 dark:text-zinc-400">
                                        <li><a href="/docs" className="hover:text-zinc-900 dark:hover:text-zinc-100">Knowledge Base</a></li>
                                        <li><a href="/case-studies" className="hover:text-zinc-900 dark:hover:text-zinc-100">Case Studies</a></li>
                                        <li><a href="/about/mahmoud-sakr" className="hover:text-zinc-900 dark:hover:text-zinc-100">Author & Expert</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 className="font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Legal</h4>
                                    <ul className="space-y-3 text-zinc-500 dark:text-zinc-400">
                                        <li><a href="/privacy-policy" className="hover:text-zinc-900 dark:hover:text-zinc-100">Privacy Policy</a></li>
                                        <li><a href="/terms-of-service" className="hover:text-zinc-900 dark:hover:text-zinc-100">Terms of Service</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row items-center justify-between pt-8 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-400 dark:text-zinc-600">
                                <div>&copy; {new Date().getFullYear()} Musoftwares. All rights reserved. Laravel v{laravelVersion} (PHP v{phpVersion})</div>
                                <div className="mt-4 md:mt-0 flex space-x-4">
                                    <a href="#" className="hover:text-zinc-900 dark:hover:text-zinc-100">LinkedIn</a>
                                    <a href="#" className="hover:text-zinc-900 dark:hover:text-zinc-100">GitHub</a>
                                    <a href="#" className="hover:text-zinc-900 dark:hover:text-zinc-100">Medium</a>
                                </div>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>

            {/* PWA Floating Install Banner */}
            {showBanner && !isStandalone && (isInstallable || isIOS) && (
                <div className="fixed bottom-6 left-6 right-6 md:left-auto md:right-6 md:w-96 z-50 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl p-4 flex items-start gap-3 animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <img src="/favicon-48x48.png" className="w-10 h-10 object-contain rounded-xl flex-shrink-0" alt="Musoftware Logo" />
                    <div className="flex-1 min-w-0">
                        <h4 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Install Musoftware</h4>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                            {isIOS 
                                ? "Add Musoftware to your Home Screen for the best experience on iOS."
                                : "Install our app for offline support, instant access, and a clean standalone interface."
                            }
                        </p>
                        <div className="flex items-center gap-2 mt-3">
                            {isIOS ? (
                                <Link href="/install-app">
                                    <Button size="sm" className="h-8 text-xs rounded-full bg-zinc-900 text-white hover:bg-zinc-850 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                                        View Setup Guide
                                    </Button>
                                </Link>
                            ) : (
                                <Button size="sm" onClick={install} className="h-8 text-xs rounded-full bg-zinc-900 text-white hover:bg-zinc-850 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 flex items-center gap-1">
                                    <Download className="w-3.5 h-3.5" /> Install App
                                </Button>
                            )}
                            <Button size="sm" variant="ghost" onClick={() => setShowBanner(false)} className="h-8 text-xs rounded-full text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                Dismiss
                            </Button>
                        </div>
                    </div>
                    <button onClick={() => setShowBanner(false)} className="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-0.5 rounded-lg flex-shrink-0 transition-colors">
                        <X className="w-4 h-4" />
                    </button>
                </div>
            )}
        </>
    );
}
