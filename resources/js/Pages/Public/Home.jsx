import { Button } from '@/Components/ui/button';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';

export default function Home({ canLogin, canRegister }) {
    const statsRef = useRef(null);
    const [statsVisible, setStatsVisible] = useState(false);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedProject, setSelectedProject] = useState(null);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setStatsVisible(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.1 }
        );

        if (statsRef.current) {
            observer.observe(statsRef.current);
        }

        return () => {
            observer.disconnect();
        };
    }, []);

    const openModal = (project) => {
        setSelectedProject(project);
        setIsModalOpen(true);
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setSelectedProject(null);
        document.body.style.overflow = 'auto';
    };

    // Helper for animated counters
    const AnimatedCounter = ({ value, label, isVisible }) => {
        const [count, setCount] = useState(0);

        useEffect(() => {
            if (!isVisible) return;

            let start = 0;
            const end = parseFloat(value.replace(/[^0-9.]/g, ''));
            const duration = 2000;
            const increment = end / (duration / 16); // 60fps

            const timer = setInterval(() => {
                start += increment;
                if (start >= end) {
                    setCount(end);
                    clearInterval(timer);
                } else {
                    setCount(start);
                }
            }, 16);

            return () => clearInterval(timer);
        }, [isVisible, value]);

        const formatted = value.includes('M')
            ? `$${count.toFixed(1)}M`
            : value.includes(',')
                ? Math.floor(count).toLocaleString()
                : Math.floor(count).toString();

        return (
            <div className="flex flex-col items-center">
                <span className="text-4xl font-bold text-white">{formatted}</span>
                <span className="text-sm text-gray-400 mt-2">{label}</span>
            </div>
        );
    };

    const projects = [
        { id: 1, title: 'Fintech Dashboard', category: 'Web App', image: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80', url: '#' },
        { id: 2, title: 'Fitness Tracker', category: 'Mobile', image: 'https://images.unsplash.com/photo-1512428559087-560fa5ceab42?auto=format&fit=crop&w=600&h=800&q=80', url: '#' },
        { id: 3, title: 'Eco Brand Identity', category: 'Design', image: 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=600&q=80', url: '#' },
        { id: 4, title: 'Fashion E-commerce', category: 'Web', image: 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?auto=format&fit=crop&w=600&h=800&q=80', url: '#' },
        { id: 5, title: 'Analytics Platform', category: 'Data', image: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=600&q=80', url: '#' }
    ];

    return (
        <PublicLayout>
            <Head title="Home" />

            <style dangerouslySetInnerHTML={{__html: `
                @keyframes mesh {
                    0% { background-position: 0% 0%; }
                    50% { background-position: 100% 100%; }
                    100% { background-position: 0% 0%; }
                }
                .bg-mesh {
                    background: radial-gradient(at 0% 0%, rgba(17,24,39,1) 0%, transparent 50%),
                                radial-gradient(at 50% 0%, rgba(55,65,81,1) 0%, transparent 50%),
                                radial-gradient(at 100% 0%, rgba(31,41,55,1) 0%, transparent 50%);
                    background-size: 200% 200%;
                    animation: mesh 15s ease infinite;
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .animate-word {
                    display: inline-block;
                    animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                    opacity: 0;
                }
                .delay-1 { animation-delay: 0.1s; }
                .delay-2 { animation-delay: 0.3s; }
                .delay-3 { animation-delay: 0.5s; }
            `}} />

            {/* 1. Hero Section */}
            <section className="relative min-h-screen flex items-center justify-center overflow-hidden bg-gray-900 bg-mesh pt-16">
                <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center text-center">

                    <span className="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-gray-300 backdrop-blur-sm mb-8 border border-white/20">
                        <span className="text-blue-400 mr-2">✦</span> All-in-one Business Platform
                    </span>

                    <h1 className="text-5xl md:text-7xl font-extrabold text-white tracking-tight leading-tight mb-6" style={{ fontFamily: 'Sora, sans-serif' }}>
                        <span className="animate-word delay-1">Manage.</span>{' '}
                        <span className="animate-word delay-2 text-blue-400">Invoice.</span>{' '}
                        <br className="hidden sm:block" />
                        <span className="animate-word delay-3">Grow.</span>
                    </h1>

                    <p className="mt-4 text-xl text-gray-300 max-w-2xl mx-auto" style={{ fontFamily: 'DM Sans, sans-serif' }}>
                        ERP, Freelancing marketplace, and services platform — all in one place.
                    </p>

                    <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <Button size="lg" className="bg-blue-600 hover:bg-blue-700 text-white rounded-full px-8">
                            Get Started Free &rarr;
                        </Button>
                        <Button size="lg" variant="outline" className="text-gray-900 bg-white hover:bg-gray-100 rounded-full px-8">
                            Watch Demo &#9655;
                        </Button>
                    </div>

                    {/* Hero Image/Mockup */}
                    <div className="mt-16 relative w-full max-w-5xl mx-auto">
                        <div className="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl blur opacity-30 animate-pulse"></div>
                        <div className="relative rounded-xl bg-gray-800 border border-gray-700 shadow-2xl overflow-hidden aspect-video">
                            <div className="h-8 bg-gray-900 border-b border-gray-700 flex items-center px-4 space-x-2">
                                <div className="w-3 h-3 rounded-full bg-red-500"></div>
                                <div className="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div className="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <div className="p-8 flex gap-6 h-full">
                                <div className="w-64 bg-gray-900 rounded-lg p-4 flex flex-col gap-4">
                                    <div className="h-8 bg-gray-800 rounded w-full"></div>
                                    <div className="h-4 bg-gray-800 rounded w-3/4"></div>
                                    <div className="h-4 bg-gray-800 rounded w-1/2"></div>
                                    <div className="h-4 bg-gray-800 rounded w-5/6 mt-8"></div>
                                </div>
                                <div className="flex-1 flex flex-col gap-6">
                                    <div className="flex gap-4">
                                        <div className="h-24 bg-gray-900 rounded-lg flex-1"></div>
                                        <div className="h-24 bg-gray-900 rounded-lg flex-1"></div>
                                        <div className="h-24 bg-gray-900 rounded-lg flex-1"></div>
                                    </div>
                                    <div className="flex-1 bg-gray-900 rounded-lg"></div>
                                </div>
                            </div>
                        </div>

                        {/* Floating stat cards */}
                        <div className="absolute -left-12 top-1/4 bg-white p-4 rounded-xl shadow-xl border border-gray-100 animate-bounce hidden md:block" style={{ animationDuration: '3s' }}>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-bold">✓</div>
                                <div>
                                    <div className="text-sm text-gray-500 font-medium">Invoice Paid</div>
                                    <div className="text-lg font-bold text-gray-900">+$1,250.00</div>
                                </div>
                            </div>
                        </div>
                        <div className="absolute -right-12 bottom-1/4 bg-white p-4 rounded-xl shadow-xl border border-gray-100 animate-bounce hidden md:block" style={{ animationDuration: '4s', animationDelay: '1s' }}>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">👥</div>
                                <div>
                                    <div className="text-sm text-gray-500 font-medium">New Client</div>
                                    <div className="text-lg font-bold text-gray-900">Acme Corp</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 2. Stats Bar */}
                <div className="absolute bottom-0 left-0 w-full bg-gray-900/80 backdrop-blur-md border-t border-gray-800 py-6" ref={statsRef}>
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-gray-800">
                            <AnimatedCounter value="312" label="Active Clients" isVisible={statsVisible} />
                            <AnimatedCounter value="1,240" label="Invoices" isVisible={statsVisible} />
                            <AnimatedCounter value="$2.4M" label="Processed" isVisible={statsVisible} />
                            <AnimatedCounter value="24" label="Businesses" isVisible={statsVisible} />
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. Services Section */}
            <section className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-16">
                        <h2 className="text-sm font-semibold text-blue-600 uppercase tracking-wide">Browse our marketplace</h2>
                        <p className="mt-2 text-4xl font-extrabold text-gray-900 tracking-tight">Explore Services</p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        {/* Service Mockup 1 */}
                        <div className="group rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-lg transition-all">
                            <div className="aspect-[4/3] bg-gray-100 relative">
                                <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80" alt="Web Development" className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300" />
                                <div className="absolute top-3 left-3 bg-white px-2 py-1 rounded text-xs font-bold text-gray-900 shadow-sm">Web Dev</div>
                            </div>
                            <div className="p-5">
                                <div className="flex items-center gap-2 mb-3">
                                    <div className="w-6 h-6 rounded-full bg-gray-200"></div>
                                    <span className="text-sm font-medium text-gray-700">Alex Frontend</span>
                                </div>
                                <h3 className="font-semibold text-gray-900 line-clamp-2 mb-2">I will build a modern React JS landing page</h3>
                                <div className="flex items-center gap-1 text-sm text-gray-500 mb-4">
                                    <span className="text-yellow-400">★</span> 4.9 (120)
                                </div>
                                <div className="border-t border-gray-100 pt-4 flex justify-between items-center">
                                    <span className="text-xs text-gray-500">Starting at</span>
                                    <span className="font-bold text-gray-900">$150</span>
                                </div>
                            </div>
                        </div>

                        {/* Service Mockup 2 */}
                        <div className="group rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-lg transition-all">
                            <div className="aspect-[4/3] bg-gray-100 relative">
                                <img src="https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&w=600&q=80" alt="Logo Design" className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300" />
                                <div className="absolute top-3 left-3 bg-white px-2 py-1 rounded text-xs font-bold text-gray-900 shadow-sm">Design</div>
                            </div>
                            <div className="p-5">
                                <div className="flex items-center gap-2 mb-3">
                                    <div className="w-6 h-6 rounded-full bg-gray-200"></div>
                                    <span className="text-sm font-medium text-gray-700">Sarah Creative</span>
                                </div>
                                <h3 className="font-semibold text-gray-900 line-clamp-2 mb-2">I will design a minimalist startup logo</h3>
                                <div className="flex items-center gap-1 text-sm text-gray-500 mb-4">
                                    <span className="text-yellow-400">★</span> 5.0 (84)
                                </div>
                                <div className="border-t border-gray-100 pt-4 flex justify-between items-center">
                                    <span className="text-xs text-gray-500">Starting at</span>
                                    <span className="font-bold text-gray-900">$85</span>
                                </div>
                            </div>
                        </div>

                        {/* Service Mockup 3 */}
                        <div className="group rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-lg transition-all hidden sm:block">
                            <div className="aspect-[4/3] bg-gray-100 relative">
                                <img src="https://images.unsplash.com/photo-1432888498266-38ffec3eaf0a?auto=format&fit=crop&w=600&q=80" alt="Content Writing" className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300" />
                                <div className="absolute top-3 left-3 bg-white px-2 py-1 rounded text-xs font-bold text-gray-900 shadow-sm">Writing</div>
                            </div>
                            <div className="p-5">
                                <div className="flex items-center gap-2 mb-3">
                                    <div className="w-6 h-6 rounded-full bg-gray-200"></div>
                                    <span className="text-sm font-medium text-gray-700">Wordsmith John</span>
                                </div>
                                <h3 className="font-semibold text-gray-900 line-clamp-2 mb-2">I will write SEO optimized blog articles</h3>
                                <div className="flex items-center gap-1 text-sm text-gray-500 mb-4">
                                    <span className="text-yellow-400">★</span> 4.8 (210)
                                </div>
                                <div className="border-t border-gray-100 pt-4 flex justify-between items-center">
                                    <span className="text-xs text-gray-500">Starting at</span>
                                    <span className="font-bold text-gray-900">$40</span>
                                </div>
                            </div>
                        </div>

                        {/* Service Mockup 4 */}
                        <div className="group rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-lg transition-all hidden lg:block">
                            <div className="aspect-[4/3] bg-gray-100 relative">
                                <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=600&q=80" alt="Data Analysis" className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300" />
                                <div className="absolute top-3 left-3 bg-white px-2 py-1 rounded text-xs font-bold text-gray-900 shadow-sm">Data</div>
                            </div>
                            <div className="p-5">
                                <div className="flex items-center gap-2 mb-3">
                                    <div className="w-6 h-6 rounded-full bg-gray-200"></div>
                                    <span className="text-sm font-medium text-gray-700">Data Pro Mike</span>
                                </div>
                                <h3 className="font-semibold text-gray-900 line-clamp-2 mb-2">I will create a dynamic PowerBI dashboard</h3>
                                <div className="flex items-center gap-1 text-sm text-gray-500 mb-4">
                                    <span className="text-yellow-400">★</span> 5.0 (42)
                                </div>
                                <div className="border-t border-gray-100 pt-4 flex justify-between items-center">
                                    <span className="text-xs text-gray-500">Starting at</span>
                                    <span className="font-bold text-gray-900">$200</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-12 text-center">
                        <Button variant="outline" className="px-8 py-6 rounded-full text-blue-600 border-blue-200 hover:bg-blue-50 font-semibold">
                            Browse All Services &rarr;
                        </Button>
                    </div>
                </div>
            </section>

            {/* 4. Features Section */}
            <section className="py-24 bg-gray-50 overflow-hidden">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Block 1: ERP (Text Left, Image Right) */}
                    <div className="flex flex-col lg:flex-row items-center gap-16 mb-24">
                        <div className="flex-1 lg:pr-8">
                            <div className="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 text-blue-600 mb-6">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 className="text-3xl font-bold text-gray-900 mb-4">Professional Invoicing</h3>
                            <p className="text-lg text-gray-600 mb-8">
                                Manage your business finances effortlessly. Our ERP module supports multi-currency transactions, accurate timer billing for your projects, and built-in referral tracking to help you grow.
                            </p>
                            <ul className="space-y-4">
                                {['Multi-currency support', 'Time tracking & billing', 'Referral management'].map((item, i) => (
                                    <li key={i} className="flex items-center text-gray-700">
                                        <svg className="w-5 h-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="flex-1 relative">
                            {/* Invoice Mockup */}
                            <div className="relative z-10 bg-white rounded-2xl shadow-xl border border-gray-100 p-6 transform rotate-2 hover:rotate-0 transition-transform duration-300 max-w-md mx-auto">
                                <div className="flex justify-between items-center mb-6 border-b pb-4">
                                    <div className="font-bold text-xl text-gray-800">INVOICE <span className="text-gray-400 text-sm">#INV-2023-001</span></div>
                                    <div className="text-right">
                                        <div className="text-xs text-gray-500">Amount Due</div>
                                        <div className="text-2xl font-bold text-blue-600">$1,250.00</div>
                                    </div>
                                </div>
                                <div className="space-y-3">
                                    <div className="flex justify-between text-sm"><span className="text-gray-500">Web Development (40h)</span><span className="font-medium">$1,000.00</span></div>
                                    <div className="flex justify-between text-sm"><span className="text-gray-500">Server Setup</span><span className="font-medium">$250.00</span></div>
                                </div>
                                <div className="mt-8 pt-4 border-t flex justify-end">
                                    <div className="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg font-medium text-sm">Status: Paid</div>
                                </div>
                            </div>
                            <div className="absolute top-1/2 -right-8 w-64 h-64 bg-blue-400/20 rounded-full blur-3xl -z-10"></div>
                        </div>
                    </div>

                    {/* Block 2: Freelance (Image Left, Text Right) */}
                    <div className="flex flex-col lg:flex-row-reverse items-center gap-16 mb-24">
                        <div className="flex-1 lg:pl-8">
                            <div className="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-purple-100 text-purple-600 mb-6">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 className="text-3xl font-bold text-gray-900 mb-4">Smart Freelance Marketplace</h3>
                            <p className="text-lg text-gray-600 mb-8">
                                Connect with top talent or find your next big project. Our platform features intelligent skill-based matching and real-time notifications to keep your projects moving.
                            </p>
                            <ul className="space-y-4">
                                {['Skill-based matching algorithm', 'Real-time project notifications', 'Secure proposal system'].map((item, i) => (
                                    <li key={i} className="flex items-center text-gray-700">
                                        <svg className="w-5 h-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="flex-1 relative">
                            {/* Job Board Mockup */}
                            <div className="relative z-10 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 transform -rotate-2 hover:rotate-0 transition-transform duration-300 max-w-md mx-auto">
                                <div className="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 className="font-bold text-gray-900">Senior React Developer</h4>
                                            <p className="text-sm text-gray-500">TechCorp Inc. • Remote</p>
                                        </div>
                                        <span className="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-medium">$50-$80/hr</span>
                                    </div>
                                    <p className="text-sm text-gray-600 mb-4 line-clamp-2">Looking for an experienced React developer to build a modern dashboard interface...</p>
                                    <div className="flex gap-2">
                                        <span className="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded">React</span>
                                        <span className="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded">TypeScript</span>
                                        <span className="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded">Tailwind</span>
                                    </div>
                                    <div className="mt-4 pt-4 border-t border-gray-200">
                                        <Button className="w-full bg-purple-600 hover:bg-purple-700">Submit Proposal</Button>
                                    </div>
                                </div>
                            </div>
                            <div className="absolute top-1/2 -left-8 w-64 h-64 bg-purple-400/20 rounded-full blur-3xl -z-10"></div>
                        </div>
                    </div>

                    {/* Block 3: Marketplace (Text Left, Image Right) */}
                    <div className="flex flex-col lg:flex-row items-center gap-16">
                        <div className="flex-1 lg:pr-8">
                            <div className="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-orange-100 text-orange-600 mb-6">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <h3 className="text-3xl font-bold text-gray-900 mb-4">Buy & Sell Services</h3>
                            <p className="text-lg text-gray-600 mb-8">
                                Productize your skills with Fiverr-style service packages. Buyers can easily purchase standardized offerings using secure integrated wallet payments.
                            </p>
                            <ul className="space-y-4">
                                {['Tiered service packages (Basic, Pro, Elite)', 'Integrated wallet payments', 'Secure escrow system'].map((item, i) => (
                                    <li key={i} className="flex items-center text-gray-700">
                                        <svg className="w-5 h-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="flex-1 relative">
                            {/* Service Cards Mockup */}
                            <div className="relative z-10 max-w-md mx-auto grid grid-cols-2 gap-4">
                                <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden transform translate-y-4">
                                    <div className="h-24 bg-gradient-to-br from-pink-400 to-orange-400"></div>
                                    <div className="p-4">
                                        <h4 className="font-bold text-sm mb-1">Logo Design</h4>
                                        <p className="text-xs text-gray-500 mb-2">From $99</p>
                                        <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden"><div className="w-3/4 h-full bg-orange-400"></div></div>
                                    </div>
                                </div>
                                <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden transform -translate-y-4">
                                    <div className="h-24 bg-gradient-to-br from-cyan-400 to-blue-400"></div>
                                    <div className="p-4">
                                        <h4 className="font-bold text-sm mb-1">SEO Audit</h4>
                                        <p className="text-xs text-gray-500 mb-2">From $149</p>
                                        <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden"><div className="w-full h-full bg-blue-400"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div className="absolute top-1/2 -right-8 w-64 h-64 bg-orange-400/20 rounded-full blur-3xl -z-10"></div>
                        </div>
                    </div>

                </div>
            </section>

            {/* 5. Portfolio Section */}
            <section className="py-24 bg-white border-t border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h2 className="text-sm font-semibold text-blue-600 uppercase tracking-wide">Our Work</h2>
                        <p className="mt-2 text-4xl font-extrabold text-gray-900 tracking-tight">Recent Projects</p>
                    </div>

                    {/* Category Tabs */}
                    <div className="flex flex-wrap justify-center gap-2 mb-12">
                        {['All', 'Web', 'Mobile', 'Design', 'Marketing'].map((tab, i) => (
                            <button
                                key={i}
                                className={`px-5 py-2 rounded-full text-sm font-medium transition-colors ${
                                    i === 0
                                        ? 'bg-gray-900 text-white'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }`}
                            >
                                {tab}
                            </button>
                        ))}
                    </div>

                    {/* Masonry Grid (CSS columns approach) */}
                    <div className="columns-1 sm:columns-2 lg:columns-3 gap-6 space-y-6">
                        {projects.map((project) => (
                            <div
                                key={project.id}
                                className="group relative rounded-xl overflow-hidden break-inside-avoid shadow-sm hover:shadow-xl transition-all cursor-pointer"
                                onClick={() => openModal(project)}
                            >
                                <img src={project.image} alt={project.title} className="w-full object-cover transform group-hover:scale-105 transition-transform duration-500" />
                                <div className="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
                                    <span className="text-blue-400 text-xs font-bold uppercase tracking-wider mb-1">{project.category}</span>
                                    <h3 className="text-xl font-bold text-white mb-3">{project.title}</h3>
                                    <Button size="sm" className="w-fit bg-white text-gray-900 hover:bg-gray-100 pointer-events-none">View Project</Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Full-screen Portfolio Modal */}
            {isModalOpen && selectedProject && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 sm:p-6 backdrop-blur-sm transition-opacity">
                    <div className="relative w-full max-w-6xl max-h-[90vh] bg-gray-900 rounded-2xl overflow-hidden shadow-2xl flex flex-col md:flex-row">
                        {/* Close button */}
                        <button
                            onClick={closeModal}
                            className="absolute top-4 right-4 z-10 p-2 bg-black/50 hover:bg-black/80 text-white rounded-full transition-colors"
                        >
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        {/* Image Gallery Area */}
                        <div className="w-full md:w-2/3 bg-black flex items-center justify-center relative overflow-hidden group">
                            <img
                                src={selectedProject.image}
                                alt={selectedProject.title}
                                className="max-w-full max-h-[50vh] md:max-h-[90vh] object-contain"
                            />
                            {/* Fake gallery navigation */}
                            <button className="absolute left-4 p-2 bg-black/50 hover:bg-black/80 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <button className="absolute right-4 p-2 bg-black/50 hover:bg-black/80 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>

                        {/* Project Details */}
                        <div className="w-full md:w-1/3 p-8 flex flex-col overflow-y-auto max-h-[40vh] md:max-h-[90vh] bg-gray-900 text-gray-300">
                            <div className="mb-8">
                                <span className="text-blue-400 text-xs font-bold uppercase tracking-wider">{selectedProject.category}</span>
                                <h2 className="text-3xl font-bold text-white mt-2 mb-4">{selectedProject.title}</h2>
                                <p className="text-gray-400 leading-relaxed">
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                                </p>
                            </div>

                            <div className="space-y-4 mb-8">
                                <div>
                                    <h4 className="text-sm font-semibold text-white uppercase mb-1">Client</h4>
                                    <p className="text-gray-400">Acme Corporation</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-white uppercase mb-1">Role</h4>
                                    <p className="text-gray-400">Lead Designer, Full Stack Developer</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-semibold text-white uppercase mb-1">Tech Stack</h4>
                                    <div className="flex flex-wrap gap-2 mt-2">
                                        <span className="px-2 py-1 bg-gray-800 rounded text-xs">React</span>
                                        <span className="px-2 py-1 bg-gray-800 rounded text-xs">Tailwind</span>
                                        <span className="px-2 py-1 bg-gray-800 rounded text-xs">Laravel</span>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-auto pt-6 border-t border-gray-800">
                                <a href={selectedProject.url} target="_blank" rel="noopener noreferrer">
                                    <Button className="w-full bg-blue-600 hover:bg-blue-700 text-white">
                                        View Live Site
                                        <svg className="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </Button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* 6. CTA Section */}
            <section className="py-24 bg-white">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="relative rounded-3xl bg-gray-900 overflow-hidden shadow-2xl">
                        {/* Gradient border effect via pseudo element */}
                        <div className="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-orange-500 opacity-20"></div>
                        <div className="absolute inset-[2px] bg-gray-900 rounded-3xl z-0"></div>

                        <div className="relative z-10 px-6 py-16 sm:px-12 sm:py-20 flex flex-col items-center text-center">
                            <h2 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-6">
                                Ready to grow your business?
                            </h2>
                            <p className="text-xl text-gray-300 max-w-2xl mb-10">
                                Join thousands of professionals using our platform to manage their business, find clients, and sell services.
                            </p>
                            <Link href={route('register')}>
                                <Button size="lg" className="bg-white text-gray-900 hover:bg-gray-100 rounded-full px-8 py-6 text-lg font-bold shadow-lg transform transition-transform hover:scale-105">
                                    Create Free Account &rarr;
                                </Button>
                            </Link>
                            <p className="mt-6 text-sm text-gray-500">No credit card required. 14-day free trial.</p>
                        </div>

                        {/* Decorative blobs */}
                        <div className="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 rounded-full bg-blue-500/30 blur-3xl z-0"></div>
                        <div className="absolute bottom-0 left-0 -ml-20 -mb-20 w-64 h-64 rounded-full bg-purple-500/30 blur-3xl z-0"></div>
                    </div>
                </div>
            </section>

        </PublicLayout>
    );
}
