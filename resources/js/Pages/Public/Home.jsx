import { useState, useRef, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/ui/accordion";
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Smartphone, Globe, Bot, Server, CheckCircle2, MessageSquare, ArrowRight, UserCircle, Star, Terminal } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';
import { __ } from '@/lib/i18n';

gsap.registerPlugin(ScrollTrigger);

export default function Home({ dbProjects = [] }) {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";
    const [scopingPrompt, setScopingPrompt] = useState('');

    const handleSandboxScope = (e) => {
        e.preventDefault();
        if (!scopingPrompt.trim()) return;
        router.visit(`/register?prefill_desc=${encodeURIComponent(scopingPrompt.trim())}`);
    };

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

    const openWhatsApp = (msg) => {
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    const services = [
        {
            title: "Mobile Applications",
            icon: Smartphone,
            features: ["Native Android", "Native iOS", "Cross Platform"],
            cta: "Request an App",
            msg: "Hello Mahmoud, I want to build a mobile app."
        },
        {
            title: "Websites",
            icon: Globe,
            features: ["Corporate Sites", "E-commerce", "Dashboards"],
            cta: "Request a Website",
            msg: "Hello Mahmoud, I need a professional website."
        },
        {
            title: "Bots & AI",
            icon: Bot,
            features: ["Telegram Bots", "WhatsApp Automation", "AI Assistants"],
            cta: "Request a Bot",
            msg: "Hello Mahmoud, I am interested in Bot & AI automation."
        },
        {
            title: "Servers & Systems",
            icon: Server,
            features: ["VPS Setup", "Deployment", "Infrastructure"],
            cta: "Request Server Setup",
            msg: "Hello Mahmoud, I need help with servers and deployment."
        }
    ];

    const portfolio = useMemo(() => {
        if (dbProjects && dbProjects.length > 0) {
            const list = dbProjects.slice(0, 2).map(p => ({
                name: p.title,
                img: p.img,
                description: p.desc,
                features: p.techs && p.techs.length > 0 ? p.techs.map(t => `${t} implementation`) : ["Custom Business Logic", "Premium Responsive UI", "Database Integration", "Testing & Deployment"],
                techs: p.techs || [],
                live_url: p.live_url
            }));
            
            list.push({
                name: "Your Next Project?",
                isPromotional: true,
                description: "Are you ready to build something exceptional? We have the engineering power to turn your complex business logic into a scalable reality.",
                features: [
                    "Custom Architecture",
                    "Scalable Infrastructure",
                    "Direct Communication",
                    "Ongoing Support"
                ],
                techs: ["Future-proof"]
            });
            return list;
        }

        return [
            {
                name: "AMC Academy",
                img: "/images/portfolio/amcacademy.jpg",
                description: "AMC Academy is an educational platform designed specifically for students to access high-quality courses, track their learning progress, and interact with instructors. It provides a complete digital learning environment with full administrative control.",
                features: [
                    "Online course streaming",
                    "Student progress tracking",
                    "Interactive exams & quizzes",
                    "Instructor dashboard",
                    "Secure payment gateway"
                ],
                techs: ["React", "Laravel", "PostgreSQL"]
            },
            {
                name: "AmcTasks.com",
                img: "/images/portfolio/amctasks.jpg",
                description: "AmcTasks is a powerful social media automation platform designed to help businesses manage their online presence. Instead of jumping between different apps, you can schedule posts across multiple platforms, set up recurring content, and manage hundreds of Facebook comments automatically from a single dashboard.",
                features: [
                    "Social media post scheduling",
                    "Automated bulk comment replies",
                    "SMS marketing campaigns",
                    "Recurring content automation",
                    "Analytics and export tools"
                ],
                techs: ["Laravel", "Redis", "Firebase"]
            },
            {
                name: "Your Next Project?",
                isPromotional: true,
                description: "Are you ready to build something exceptional? We have the engineering power to turn your complex business logic into a scalable reality.",
                features: [
                    "Custom Architecture",
                    "Scalable Infrastructure",
                    "Direct Communication",
                    "Ongoing Support"
                ],
                techs: ["Future-proof"]
            }
        ];
    }, [dbProjects]);

    const techCategories = [
        {
            title: "Frontend",
            techs: ["Next.js", "React", "TypeScript", "Tailwind CSS", "Shadcn UI"]
        },
        {
            title: "Backend",
            techs: ["Node.js", "Express.js", "NestJS", "Laravel", "PHP"]
        },
        {
            title: "Databases & Caching",
            techs: ["PostgreSQL", "MySQL", "Redis", "SQLite"]
        },
        {
            title: "Infrastructure & DevOps",
            techs: ["Docker", "VPS", "Nginx", "Linux", "CI/CD Pipelines", "Cloud Deployments"]
        },
        {
            title: "Automation & Integrations",
            techs: ["Telegram APIs", "WhatsApp Integrations", "Payment Gateways", "Email Services", "Webhooks"]
        },
        {
            title: "AI & Data",
            techs: ["OpenAI APIs", "AI Agents", "Chatbots", "Vector Databases", "RAG Systems"]
        },
        {
            title: "Mobile",
            techs: ["React Native", "Expo", "Android Deployment"]
        },
        {
            title: "Tools & Workflow",
            techs: ["Git", "GitHub", "Postman", "Prisma", "REST APIs"]
        }
    ];

    return (
        <PublicLayout>
            <Head>
                <title>Musoftware | Turning Ideas into Reality</title>
                <meta name="description" content="Musoftware - We build scalable systems, mobile apps, and robust websites." />
            </Head>

            <FloatingWhatsAppButton phoneNumber={phoneNumber} defaultMessage="Hello Mahmoud, I want to discuss a new project!" />

            <div ref={mainRef} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white">
                
                {/* 1. Hero Section (Split Layout) */}
                <section className="pt-32 pb-16 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="grid lg:grid-cols-12 gap-12 items-center">
                        <div className="lg:col-span-7 space-y-8 text-start">
                            <h1 className="gsap-fade-up text-4xl sm:text-5xl md:text-6xl font-black tracking-tight text-[#111111] leading-[1.1]">
                                Architecting <br />
                                <span className="bg-clip-text text-transparent bg-gradient-to-r from-zinc-500 to-zinc-950">
                                    Future-Proof Systems
                                </span>
                            </h1>
                            <p className="gsap-fade-up text-lg text-[#666666] leading-relaxed max-w-xl">
                                Stop wrestling with disjointed, fragile code templates. We engineer enterprise-grade business systems, custom database engines, and automated webhook pipelines that scale.
                            </p>
                            
                            <div className="gsap-fade-up max-w-xl">
                                <form onSubmit={handleSandboxScope} className="p-2 bg-white border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] flex items-center gap-2">
                                    <input
                                        type="text"
                                        placeholder="What system are you building today? (e.g. Gold POS, SMS Gate)"
                                        className="flex-1 bg-transparent px-4 py-2 text-sm text-[#111111] outline-none placeholder:text-[#888888]"
                                        value={scopingPrompt}
                                        onChange={(e) => setScopingPrompt(e.target.value)}
                                    />
                                    <Button
                                        type="submit"
                                        className="rounded-xl bg-[#111111] text-white hover:bg-[#333333] font-bold px-6 py-2 text-xs uppercase tracking-wider cursor-pointer"
                                    >
                                        Scope System
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <div className="lg:col-span-5 gsap-fade-up hidden lg:block">
                            <div className="p-6 bg-zinc-900 rounded-3xl border border-zinc-800 shadow-2xl font-mono text-xs text-zinc-400 space-y-4">
                                <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
                                    <div className="flex items-center gap-1.5">
                                        <span className="w-3 h-3 rounded-full bg-red-500/80"></span>
                                        <span className="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                                        <span className="w-3 h-3 rounded-full bg-green-500/80"></span>
                                    </div>
                                    <span className="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Scoping Console</span>
                                </div>
                                <div className="space-y-2">
                                    <p className="text-zinc-500">$ musoftwares init system-core</p>
                                    <p className="text-emerald-400 font-bold">✔ Initializing system blueprints...</p>
                                    <p className="text-zinc-500">$ musoftwares register --addons=payments,whatsapp</p>
                                    <div className="p-3 bg-zinc-950 rounded-xl border border-zinc-800/60 text-zinc-350 space-y-1">
                                        <p className="text-[10px] text-zinc-500 uppercase tracking-widest font-extrabold mb-1">Architecture Blueprints</p>
                                        <p>• Database: PostgreSQL Isolated Schema</p>
                                        <p>• Server Node: Frankfurt VPS Proxy-Enabled</p>
                                        <p>• Webhooks: Real-time Meta Gateway</p>
                                    </div>
                                    <p className="text-zinc-500">$ systemctl status musoftwares-pulse</p>
                                    <p className="text-emerald-400 flex items-center gap-1.5">
                                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active nodes online. Average build duration: 1.8s
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* LIVE TELEMETRY TICKER */}
                <section className="w-full bg-[#111111] text-zinc-400 py-4 border-t border-b border-zinc-850 overflow-hidden reveal-section">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4 text-xs font-mono">
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                            <span className="text-white font-bold uppercase tracking-wider">{__('general.scifi_system_online') || 'System Online'}</span>
                        </div>
                        <div className="flex flex-wrap items-center gap-x-8 gap-y-2">
                            <span className="flex items-center gap-2">
                                <span className="text-zinc-500 font-extrabold">Active Projects:</span>
                                <span className="text-white font-bold">14 builds</span>
                            </span>
                            <span className="flex items-center gap-2">
                                <span className="text-zinc-500 font-extrabold">Completed Jobs:</span>
                                <span className="text-white font-bold">3,842 tasks</span>
                            </span>
                            <span className="flex items-center gap-2">
                                <span className="text-zinc-500 font-extrabold">Response Yield:</span>
                                <span className="text-emerald-450 font-bold">99.98% uptime</span>
                            </span>
                        </div>
                    </div>
                </section>

                {/* ECOSYSTEM REGISTRY */}
                <section className="w-full bg-[#fafafa] py-24 border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <span className="px-3 py-1 text-xs font-semibold rounded-full bg-[#f4f4f5] border border-[#e5e5e5] text-[#666666] mb-3 inline-block uppercase tracking-wider">
                                {__('general.ecosystem_registry')}
                            </span>
                            <h2 className="text-3xl md:text-4xl font-extrabold tracking-tight mb-4 text-[#111111]">
                                {__('general.explore_our_apps')}
                            </h2>
                            <p className="text-[#666666] max-w-2xl mx-auto leading-relaxed">
                                {__('general.no_need_to_build')}
                            </p>
                        </div>

                        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                            {[
                                { name: __('general.erp_system'), cat: 'Core SaaS', desc: __('general.erp_desc') },
                                { name: __('general.crm_system'), cat: 'Core SaaS', desc: __('general.crm_desc') },
                                { name: __('general.whatsapp_bot'), cat: 'Marketing', desc: __('general.whatsapp_bot_desc') },
                                { name: __('general.gold_pos'), cat: 'POS Engine', desc: __('general.gold_pos_desc') },
                                { name: __('general.sms_gateway'), cat: 'Messaging', desc: __('general.sms_gateway_desc') },
                                { name: __('general.booking_system'), cat: 'Core SaaS', desc: __('general.booking_system_desc') }
                            ].map((tool, idx) => (
                                <div key={idx} className="p-6 rounded-2xl bg-white border border-[#e5e5e5] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_30px_-10px_rgba(0,0,0,0.1)] transition-all flex flex-col justify-between group">
                                    <div>
                                        <div className="flex items-center justify-between mb-3">
                                            <h4 className="font-bold text-[#111111] text-base group-hover:text-[#444444] transition-colors">{tool.name}</h4>
                                            <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-[#f4f4f5] text-[#666666] border border-[#e5e5e5]/50">{tool.cat}</span>
                                        </div>
                                        <p className="text-xs text-[#666666] leading-relaxed mb-4">{tool.desc}</p>
                                    </div>
                                    <Link href="/register" className="text-xs font-bold text-[#111111] hover:underline flex items-center gap-1">
                                        {__('general.deploy_this_tool')} ➔
                                    </Link>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* 3. Story-Format Portfolio */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">{__('general.our_success_stories')}</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">{__('general.real_problems_engineered_solutions_measu')}</p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        {portfolio.map((project, idx) => (
                            <div key={idx} className={`gsap-fade-up flex flex-col bg-white rounded-2xl overflow-hidden shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_30px_-10px_rgba(0,0,0,0.1)] transition-all duration-300 border ${project.isPromotional ? 'border-[#111111] border-2 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.15)]' : 'border-[#e5e5e5]'}`}>
                                <div className="h-56 overflow-hidden relative bg-[#f4f4f5]">
                                    {project.isPromotional ? (
                                        <div className="absolute inset-0 bg-[#111111] text-white flex flex-col items-center justify-center p-6 text-center">
                                            <span className="text-sm font-bold uppercase tracking-widest text-[#a3a3a3] mb-2">{__('general.reserved_for')}</span>
                                            <span className="text-3xl font-extrabold">{__('general.your_vision')}</span>
                                        </div>
                                    ) : (
                                        <img src={project.img} alt={project.name} className="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" />
                                    )}
                                </div>
                                <div className="p-8 lg:p-10 flex flex-col flex-grow">
                                    <h3 className="text-2xl font-bold mb-4 text-[#111111]">{project.name}</h3>
                                    <p className="text-[#666666] leading-relaxed text-[15px] mb-8">{project.description}</p>
                                    
                                    <div className="mb-8 flex-grow">
                                        <h4 className="font-bold text-[#111111] uppercase text-xs tracking-widest mb-4">{__('general.key_features')}</h4>
                                        <ul className="space-y-3">
                                            {project.features.map((feature, i) => (
                                                <li key={i} className="flex items-start gap-3 text-[#444444]">
                                                    <CheckCircle2 className="w-4 h-4 text-[#111111] mt-0.5 shrink-0" />
                                                    <span className="text-sm font-medium">{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    <div className="flex flex-wrap gap-2 mb-8">
                                        {project.techs.map((tech, i) => (
                                            <span key={i} className="bg-[#f4f4f5] border border-[#e5e5e5] px-3 py-1 rounded-md text-[11px] font-bold text-[#111111] uppercase tracking-wider">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>

                                    <Button 
                                        onClick={() => openWhatsApp(project.isPromotional ? "Hello Mahmoud, I'm ready to start my next project." : `Hello Mahmoud, I am interested in a project similar to ${project.name}`)}
                                        variant="outline"
                                        className={`mt-auto w-full rounded-xl py-7 text-sm font-bold tracking-wide uppercase transition-all duration-300 flex items-center justify-center gap-3 ${project.isPromotional ? 'bg-[#111111] text-white hover:bg-[#333333]' : 'border-[#e5e5e5] text-[#111111] hover:bg-[#111111] hover:text-white'}`}
                                    >
                                        {project.isPromotional ? "Start Your Project" : "I want a similar project"} <ArrowRight className="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-16 text-center">
                        <Link href="/portfolio" className="inline-flex items-center gap-3 px-8 py-5 bg-[#f4f4f5] hover:bg-[#e5e5e5] text-[#111111] rounded-xl text-sm font-bold uppercase tracking-widest transition-colors duration-300">
                            {__('general.view_all_projects')}<ArrowRight className="w-5 h-5" />
                        </Link>
                    </div>
                </section>

                {/* 2. Trust Section & Social Proof */}
                <section className="py-20 bg-[#111111] text-white reveal-section border-t border-b border-[#222222]">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">100<span className="text-[#888888]">+</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">{__('general.projects_delivered')}</span>
                            </div>
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">50<span className="text-[#888888]">+</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">{__('general.happy_clients')}</span>
                            </div>
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">10<span className="text-[#888888]">+</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">{__('general.years_experience')}</span>
                            </div>
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">&lt;1<span className="text-[#888888]">hr</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">{__('general.avg_response_time')}</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 4. Client Testimonials */}
                <section className="py-24 bg-[#fafafa] border-y border-[#e5e5e5] px-6 lg:px-8 reveal-section">
                    <div className="max-w-7xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">{__('general.real_experiences')}</h2>
                            <p className="gsap-fade-up text-lg text-[#666666]">{__('general.dont_just_take_our_word_for_it')}</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {[
                                "Working with Mahmoud was a game changer for our business. The technical delivery was flawless, and the communication was always clear and direct.",
                                "We had a complex ERP requirement and were tired of generic agencies. Mahmoud built exactly what we needed with incredible precision.",
                                "Fast, secure, and reliable. The bot automation saved us hundreds of hours every month. Highly recommended for any serious business."
                            ].map((testimonial, idx) => (
                                <div key={idx} className="gsap-fade-up bg-white p-10 rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-[#e5e5e5] hover:-translate-y-1 transition-transform duration-300">
                                    <div className="flex text-yellow-400 mb-6">
                                        <Star className="w-4 h-4 fill-current me-1" />
                                        <Star className="w-4 h-4 fill-current me-1" />
                                        <Star className="w-4 h-4 fill-current me-1" />
                                        <Star className="w-4 h-4 fill-current me-1" />
                                        <Star className="w-4 h-4 fill-current" />
                                    </div>
                                    <p className="text-[#444444] text-base leading-loose italic mb-8">
                                        "{testimonial}"
                                    </p>
                                    <div className="flex items-center gap-4">
                                        <div className="w-12 h-12 bg-[#f4f4f5] rounded-full flex items-center justify-center border border-[#e5e5e5]">
                                            <UserCircle className="w-7 h-7 text-[#888888]" />
                                        </div>
                                        <div>
                                            <h5 className="font-bold text-[#111111]">{__('general.verified_client')}</h5>
                                            <span className="text-[11px] font-semibold text-[#888888] uppercase tracking-wider">{__('general.business_owner')}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* PRICING CALCULATOR */}
                <section className="w-full py-24 bg-white border-b border-[#e5e5e5] reveal-section">
                    <div className="max-w-4xl mx-auto px-6">
                        <div className="text-center mb-16">
                            <span className="px-3 py-1 text-xs font-semibold rounded-full bg-[#f4f4f5] border border-[#e5e5e5] text-[#666666] mb-3 inline-block uppercase tracking-wider">
                                {__('general.pricing_calculator')}
                            </span>
                            <h2 className="text-3xl md:text-4xl font-extrabold tracking-tight mb-4 text-[#111111]">
                                {__('general.estimate_your_budget')}
                            </h2>
                            <p className="text-[#666666] leading-relaxed">
                                {__('general.select_desired_scale')}
                            </p>
                        </div>

                        <PricingCalculator />
                    </div>
                </section>

                {/* 5. Process Section */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-20">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">{__('general.how_to_start')}</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">{__('general.a_simple_transparent_4step_process')}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-8 relative">
                        {/* Connecting Line */}
                        <div className="hidden md:block absolute top-10 start-[10%] end-[10%] h-[2px] bg-[#f0f0f0] z-0"></div>
                        
                        {[
                            { step: "01", title: "Send Your Idea", desc: "Reach out via WhatsApp with a brief overview of what you want to build." },
                            { step: "02", title: "Discuss Details", desc: "We review the technical feasibility and align on your business goals." },
                            { step: "03", title: "Receive Offer", desc: "You get a clear proposal detailing timeline, cost, and architecture." },
                            { step: "04", title: "Execution Begins", desc: "We start coding and keep you updated every step of the way." }
                        ].map((item, idx) => (
                            <div key={idx} className="gsap-fade-up relative z-10 flex flex-col items-center text-center">
                                <div className="w-20 h-20 bg-white border-2 border-[#111111] text-[#111111] rounded-full flex items-center justify-center text-2xl font-bold mb-8 shadow-sm">
                                    {item.step}
                                </div>
                                <h3 className="text-xl font-bold mb-4">{item.title}</h3>
                                <p className="text-[#666666] leading-relaxed text-[15px]">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* 6. About Mahmoud */}
                <section className="py-24 bg-[#111111] text-white px-6 lg:px-8 reveal-section">
                    <div className="max-w-5xl mx-auto flex flex-col md:flex-row items-center gap-16">
                        <div className="gsap-fade-up w-56 h-56 md:w-72 md:h-72 rounded-full bg-[#1a1a1a] border border-[#333333] overflow-hidden flex-shrink-0 flex items-center justify-center relative">
                            {/* Inner subtle glow */}
                            <div className="absolute inset-0 bg-gradient-to-tr from-[#333333]/20 to-transparent z-10 pointer-events-none"></div>
                            <img src="/images/mahmoud-photo.jpg" alt="Eng. Mahmoud" className="w-full h-full object-cover relative z-0" />
                        </div>
                        <div className="text-center md:text-start flex-1">
                            <h2 className="gsap-fade-up text-4xl lg:text-5xl font-extrabold mb-4">{__('general.about_eng_mahmoud')}</h2>
                            <h3 className="gsap-fade-up text-[#888888] text-sm font-bold mb-8 uppercase tracking-[0.2em]">
                                {__('general.software_architect_developer')}</h3>
                            <p className="gsap-fade-up text-lg leading-loose text-[#d4d4d4] mb-10 max-w-2xl">
                                I believe in direct communication and engineering excellence. You aren't dealing with a faceless agency; you are working directly with the architect building your system. My philosophy is simple: focus on quality, ensure absolute clarity, and deliver highly scalable solutions that drive real business value.
                            </p>
                            <Button 
                                onClick={() => openWhatsApp("Hello Engineer Mahmoud, I read your profile and would like to discuss a project.")}
                                className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-colors inline-flex items-center gap-3"
                            >
                                {__('general.lets_work_together')}<ArrowRight className="w-5 h-5" />
                            </Button>
                        </div>
                    </div>
                </section>

                {/* 7 & 8. FAQ and Tech Kitchen */}
                <section className="py-24 px-6 lg:px-8 max-w-4xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">{__('general.curious_questions')}</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">{__('general.everything_you_need_to_know_before_we_st')}</p>
                    </div>

                    <div className="gsap-fade-up mb-16">
                        <Accordion type="single" collapsible className="w-full">
                            <AccordionItem value="item-1" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">{__('general.why_are_there_no_fixed_prices')}</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    {__('general.every_project_has_its_own_unique_require')}</AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="item-2" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">{__('general.what_technologies_do_you_use')}</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    {__('general.we_use_the_right_technology_for_the_job')}</AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="item-3" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">{__('general.i_dont_know_programming_what_should_i_do')}</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    {__('general.dont_worry_just_explain_your_business_id')}</AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="item-4" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">{__('general.how_long_does_execution_take')}</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    {__('general.the_timeline_depends_entirely_on_the_pro')}</AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>

                    {/* Tech Kitchen */}
                    <div className="gsap-fade-up bg-white rounded-2xl p-8 border-2 border-[#111111] shadow-[8px_8px_0px_0px_rgba(17,17,17,1)]">
                        <Accordion type="single" collapsible className="w-full border-none">
                            <AccordionItem value="tech-1" className="border-none">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline px-2 text-[#111111]">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 bg-[#111111] text-white rounded-md flex items-center justify-center">
                                            <Terminal className="w-5 h-5" />
                                        </div>
                                        {__('general.under_the_hood_what_powers_your_project')}</div>
                                </AccordionTrigger>
                                <AccordionContent className="px-2 pt-6">
                                    <p className="text-[#666666] mb-4 text-base font-medium">For the curious minds, here's some of the tech we use depending on the project's needs:</p>
                                    <p className="text-[#111111] font-bold text-lg mb-8 leading-relaxed">
                                        {__('general.we_dont_lock_projects_into_a_specific_st')}</p>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-8 gap-y-10">
                                        {techCategories.map((category, idx) => (
                                            <div key={idx} className="flex flex-col">
                                                <h4 className="text-[#888888] font-bold text-xs uppercase tracking-widest mb-4 border-b border-[#e5e5e5] pb-2">
                                                    {category.title}
                                                </h4>
                                                <div className="flex flex-wrap gap-2">
                                                    {category.techs.map((tech, tIdx) => (
                                                        <span key={tIdx} className="bg-[#f4f4f5] border border-[#e5e5e5] px-3 py-1.5 rounded-md text-[11px] font-bold text-[#111111]">
                                                            {tech}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>
                </section>

                {/* 9. Final CTA */}
                <section className="py-32 bg-[#25D366] text-white text-center reveal-section px-6 relative overflow-hidden">
                    {/* Background subtle pattern or gradient */}
                    <div className="absolute inset-0 bg-gradient-to-b from-transparent to-[#1DA851]/20"></div>
                    
                    <div className="max-w-4xl mx-auto relative z-10">
                        <h2 className="gsap-fade-up text-5xl md:text-7xl font-extrabold tracking-tight mb-8 drop-shadow-sm">
                            {__('general.ready_to_turn_your_idea_into_a_real_proj')}</h2>
                        <p className="gsap-fade-up text-xl md:text-2xl text-[#e8fceb] mb-12 max-w-3xl mx-auto font-medium leading-relaxed drop-shadow-sm">
                            {__('general.dont_wait_lets_start_the_conversation_an')}</p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I'm ready to start!")}
                            size="lg" 
                            className="gsap-fade-up bg-white text-[#1DA851] hover:bg-[#f4f4f5] rounded-full px-12 h-20 text-xl font-bold tracking-wide transition-all duration-300 hover:scale-105 shadow-2xl flex items-center justify-center gap-3 mx-auto border-4 border-white/20 bg-clip-padding"
                        >
                            <MessageSquare className="w-7 h-7" /> {__('general.start_the_conversation_now')}</Button>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}

function PricingCalculator() {
    const [scale, setScale] = useState('multi_page');
    const [addons, setAddons] = useState({
        whatsapp: false,
        payment: false,
        sms: false,
        admin: false,
    });

    const prices = {
        single_page: { base: 3500, days: 1 },
        multi_page: { base: 8000, days: 3 },
        full_portal: { base: 20000, days: 7 },
    };

    const addonsPrices = {
        whatsapp: { price: 4000, days: 1 },
        payment: { price: 3000, days: 1 },
        sms: { price: 2500, days: 0 },
        admin: { price: 3500, days: 1 },
    };

    let total = prices[scale].base;
    let days = prices[scale].days;

    Object.keys(addons).forEach((key) => {
        if (addons[key]) {
            total += addonsPrices[key].price;
            days += addonsPrices[key].days;
        }
    });

    const prefillQuery = `I want a ${scale.replace('_', ' ')} project with these features: ${Object.keys(addons)
        .filter((k) => addons[k])
        .join(', ')}`;

    return (
        <div className="p-6 md:p-8 rounded-3xl bg-white border border-[#e5e5e5] shadow-sm">
            <div className="grid md:grid-cols-2 gap-8 text-left">
                <div className="space-y-6">
                    <div>
                        <label className="text-xs font-extrabold uppercase tracking-wider text-zinc-500 mb-2 block">Project Scale</label>
                        <div className="grid grid-cols-3 gap-2">
                            {['single_page', 'multi_page', 'full_portal'].map((s) => (
                                <button
                                    key={s}
                                    type="button"
                                    onClick={() => setScale(s)}
                                    className={`px-3 py-2 text-xs font-bold rounded-xl border text-center transition cursor-pointer ${
                                        scale === s
                                            ? 'bg-zinc-900 text-white border-zinc-900'
                                            : 'bg-transparent text-zinc-600 border-zinc-200 hover:bg-zinc-50'
                                    }`}
                                >
                                    {s === 'single_page' ? 'Single Page' : s === 'multi_page' ? 'Multi Page' : 'Full Portal'}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <label className="text-xs font-extrabold uppercase tracking-wider text-zinc-500 mb-2 block">Integrations &amp; Addons</label>
                        <div className="space-y-2">
                            {[
                                { key: 'whatsapp', name: 'WhatsApp API Integration (+4,000 EGP)' },
                                { key: 'payment', name: 'Stripe/Card Gateway Setup (+3,000 EGP)' },
                                { key: 'sms', name: 'SMS Verification OTP Gateway (+2,500 EGP)' },
                                { key: 'admin', name: 'Admin/Sub-role Permissions (+3,500 EGP)' },
                            ].map((addon) => (
                                <label
                                    key={addon.key}
                                    className="flex items-center gap-3 p-3 rounded-xl border border-zinc-200/60 hover:bg-zinc-50 transition cursor-pointer select-none"
                                >
                                    <input
                                        type="checkbox"
                                        checked={addons[addon.key]}
                                        onChange={(e) => setAddons((prev) => ({ ...prev, [addon.key]: e.target.checked }))}
                                        className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                    />
                                    <span className="text-xs text-zinc-700 font-medium">{addon.name}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="flex flex-col justify-between p-6 rounded-2xl bg-zinc-50 border border-zinc-200 text-center">
                    <div>
                        <h4 className="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Estimated Budget &amp; Delivery</h4>
                        <div className="text-4xl md:text-5xl font-black text-zinc-900 my-4">
                            {total.toLocaleString()} <span className="text-sm font-semibold">EGP</span>
                        </div>
                        <div className="text-sm text-zinc-500 font-semibold mb-4">
                            Delivery timeline: <span className="text-zinc-800 font-bold">{days} {days === 1 ? 'day' : 'days'}</span>
                        </div>
                        <p className="text-[11px] text-zinc-400 italic">
                            This estimate is generated instantly based on typical architecture. Final scoping will be verified by the AI Project Manager.
                        </p>
                    </div>

                    <Link href={`/register?prefill_desc=${encodeURIComponent(prefillQuery)}`} className="mt-6">
                        <Button className="w-full h-11 rounded-xl bg-zinc-900 text-white hover:bg-zinc-800 font-bold cursor-pointer">
                            Claim this Estimate ➔
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    );
}
