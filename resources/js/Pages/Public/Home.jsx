import { useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/ui/accordion";
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import { Smartphone, Globe, Bot, Server, CheckCircle2, MessageSquare, ArrowRight, UserCircle, Star, Terminal } from 'lucide-react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useGSAP } from '@gsap/react';

gsap.registerPlugin(ScrollTrigger);

export default function Home() {
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";

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

    const portfolio = [
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
                
                {/* 1. Service Grid (Hero Replacement) */}
                <section className="pt-32 pb-20 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h1 className="gsap-fade-up text-5xl md:text-6xl font-extrabold tracking-tight mb-6 text-[#111111]">
                            What can we build for you?
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] max-w-2xl mx-auto leading-relaxed">
                            Stop wrestling with disjointed tools. We engineer highly cohesive and horizontally scalable software tailored to your business needs.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {services.map((service, idx) => (
                            <div key={idx} className="gsap-fade-up bg-white p-8 border border-[#e5e5e5] rounded-xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_30px_-10px_rgba(0,0,0,0.1)] transition-all flex flex-col h-full group">
                                <div className="w-16 h-16 bg-[#f4f4f5] group-hover:bg-[#111111] transition-colors rounded-full flex items-center justify-center mb-6">
                                    <service.icon className="w-8 h-8 text-[#111111] group-hover:text-white transition-colors" />
                                </div>
                                <h3 className="text-2xl font-bold mb-4">{service.title}</h3>
                                <ul className="space-y-3 mb-8 flex-grow">
                                    {service.features.map((feature, i) => (
                                        <li key={i} className="flex items-center gap-3 text-[#444444]">
                                            <CheckCircle2 className="w-4 h-4 text-[#111111]" />
                                            <span className="text-sm font-medium">{feature}</span>
                                        </li>
                                    ))}
                                </ul>
                                <Button 
                                    onClick={() => openWhatsApp(service.msg)}
                                    className="w-full bg-[#111111] hover:bg-[#333333] text-white rounded-lg py-6 text-sm font-bold tracking-wide uppercase transition-all"
                                >
                                    {service.cta}
                                </Button>
                            </div>
                        ))}
                    </div>
                </section>

                {/* 2. Trust Section */}
                <section className="py-20 bg-[#111111] text-white reveal-section border-t border-b border-[#222222]">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">100<span className="text-[#888888]">+</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">Projects Delivered</span>
                            </div>
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">50<span className="text-[#888888]">+</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">Happy Clients</span>
                            </div>
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">10<span className="text-[#888888]">+</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">Years Experience</span>
                            </div>
                            <div className="gsap-fade-up flex flex-col items-center">
                                <span className="text-5xl font-extrabold mb-3 text-white">&lt;1<span className="text-[#888888]">hr</span></span>
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-[#a3a3a3]">Avg Response Time</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 3. Story-Format Portfolio */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">Our Success Stories</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">Real problems. Engineered solutions. Measurable results.</p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        {portfolio.map((project, idx) => (
                            <div key={idx} className={`gsap-fade-up flex flex-col bg-white rounded-2xl overflow-hidden shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_30px_-10px_rgba(0,0,0,0.1)] transition-all duration-300 border ${project.isPromotional ? 'border-[#111111] border-2 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.15)]' : 'border-[#e5e5e5]'}`}>
                                <div className="h-56 overflow-hidden relative bg-[#f4f4f5]">
                                    {project.isPromotional ? (
                                        <div className="absolute inset-0 bg-[#111111] text-white flex flex-col items-center justify-center p-6 text-center">
                                            <span className="text-sm font-bold uppercase tracking-widest text-[#a3a3a3] mb-2">Reserved for</span>
                                            <span className="text-3xl font-extrabold">Your Vision</span>
                                        </div>
                                    ) : (
                                        <img src={project.img} alt={project.name} className="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" />
                                    )}
                                </div>
                                <div className="p-8 lg:p-10 flex flex-col flex-grow">
                                    <h3 className="text-2xl font-bold mb-4 text-[#111111]">{project.name}</h3>
                                    <p className="text-[#666666] leading-relaxed text-[15px] mb-8">{project.description}</p>
                                    
                                    <div className="mb-8 flex-grow">
                                        <h4 className="font-bold text-[#111111] uppercase text-xs tracking-widest mb-4">Key Features</h4>
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
                            View All Projects <ArrowRight className="w-5 h-5" />
                        </Link>
                    </div>
                </section>

                {/* 4. Social Proof */}
                <section className="py-24 bg-[#fafafa] border-y border-[#e5e5e5] px-6 lg:px-8 reveal-section">
                    <div className="max-w-7xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">Real Experiences</h2>
                            <p className="gsap-fade-up text-lg text-[#666666]">Don't just take our word for it.</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {[
                                "Working with Mahmoud was a game changer for our business. The technical delivery was flawless, and the communication was always clear and direct.",
                                "We had a complex ERP requirement and were tired of generic agencies. Mahmoud built exactly what we needed with incredible precision.",
                                "Fast, secure, and reliable. The bot automation saved us hundreds of hours every month. Highly recommended for any serious business."
                            ].map((testimonial, idx) => (
                                <div key={idx} className="gsap-fade-up bg-white p-10 rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-[#e5e5e5] hover:-translate-y-1 transition-transform duration-300">
                                    <div className="flex text-yellow-400 mb-6">
                                        <Star className="w-4 h-4 fill-current mr-1" />
                                        <Star className="w-4 h-4 fill-current mr-1" />
                                        <Star className="w-4 h-4 fill-current mr-1" />
                                        <Star className="w-4 h-4 fill-current mr-1" />
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
                                            <h5 className="font-bold text-[#111111]">Verified Client</h5>
                                            <span className="text-[11px] font-semibold text-[#888888] uppercase tracking-wider">Business Owner</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* 5. Process Section */}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-20">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">How to Start?</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">A simple, transparent 4-step process.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-8 relative">
                        {/* Connecting Line */}
                        <div className="hidden md:block absolute top-10 left-[10%] right-[10%] h-[2px] bg-[#f0f0f0] z-0"></div>
                        
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
                        <div className="text-center md:text-left flex-1">
                            <h2 className="gsap-fade-up text-4xl lg:text-5xl font-extrabold mb-4">About Eng. Mahmoud</h2>
                            <h3 className="gsap-fade-up text-[#888888] text-sm font-bold mb-8 uppercase tracking-[0.2em]">
                                Software Architect & Developer
                            </h3>
                            <p className="gsap-fade-up text-lg leading-loose text-[#d4d4d4] mb-10 max-w-2xl">
                                I believe in direct communication and engineering excellence. You aren't dealing with a faceless agency; you are working directly with the architect building your system. My philosophy is simple: focus on quality, ensure absolute clarity, and deliver highly scalable solutions that drive real business value.
                            </p>
                            <Button 
                                onClick={() => openWhatsApp("Hello Engineer Mahmoud, I read your profile and would like to discuss a project.")}
                                className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-colors inline-flex items-center gap-3"
                            >
                                Let's Work Together <ArrowRight className="w-5 h-5" />
                            </Button>
                        </div>
                    </div>
                </section>

                {/* 7 & 8. FAQ and Tech Kitchen */}
                <section className="py-24 px-6 lg:px-8 max-w-4xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">Curious Questions</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">Everything you need to know before we start.</p>
                    </div>

                    <div className="gsap-fade-up mb-16">
                        <Accordion type="single" collapsible className="w-full">
                            <AccordionItem value="item-1" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">Why are there no fixed prices?</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    Every project has its own unique requirements, scale, and technical challenges. We provide custom quotes based on exactly what you need to succeed, avoiding bloated generic packages.
                                </AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="item-2" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">What technologies do you use?</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    We use the right technology for the job, not just the most popular framework. Our stack is chosen based on scalability, security, and performance requirements for your specific use case.
                                </AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="item-3" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">I don't know programming, what should I do?</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    Don't worry! Just explain your business idea and goals in plain language. We will handle all the technical architecture, development, and deployment for you.
                                </AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="item-4" className="border-[#e5e5e5] py-2">
                                <AccordionTrigger className="text-xl font-bold hover:no-underline text-[#111111]">How long does execution take?</AccordionTrigger>
                                <AccordionContent className="text-[#666666] leading-relaxed text-base pt-2 pb-6">
                                    The timeline depends entirely on the project's size and complexity. After our initial discussion, you will receive a detailed roadmap with clear milestones.
                                </AccordionContent>
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
                                        Under the Hood... What powers your project?
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent className="px-2 pt-6">
                                    <p className="text-[#666666] mb-4 text-base font-medium">For the curious minds, here's some of the tech we use depending on the project's needs:</p>
                                    <p className="text-[#111111] font-bold text-lg mb-8 leading-relaxed">
                                        We don't lock projects into a specific stack. We choose the tools that make the most sense for the job.
                                    </p>
                                    
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
                            Ready to turn your idea into a real project?
                        </h2>
                        <p className="gsap-fade-up text-xl md:text-2xl text-[#e8fceb] mb-12 max-w-3xl mx-auto font-medium leading-relaxed drop-shadow-sm">
                            Don't wait. Let's start the conversation and build something amazing together.
                        </p>
                        <Button 
                            onClick={() => openWhatsApp("Hello Mahmoud, I'm ready to start!")}
                            size="lg" 
                            className="gsap-fade-up bg-white text-[#1DA851] hover:bg-[#f4f4f5] rounded-full px-12 h-20 text-xl font-bold tracking-wide transition-all duration-300 hover:scale-105 shadow-2xl flex items-center justify-center gap-3 mx-auto border-4 border-white/20 bg-clip-padding"
                        >
                            <MessageSquare className="w-7 h-7" /> Start the Conversation Now
                        </Button>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}
