import json
import os
import re

base_dir = r'D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares'
js_dir = os.path.join(base_dir, 'resources', 'js', 'Pages', 'Public')
lang_dir = os.path.join(base_dir, 'lang', 'en')

def create_premium_file(filepath, component_name, page_title, meta_desc, whatsapp_msg, category, hero_title, hero_desc, cta_title, cta_desc, cta_btn, features, lang_key):
    imports = f'''import {{ useRef }} from 'react';
import {{ Head }} from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import {{ {', '.join(set([f['icon'] for f in features] + ['ArrowRight', 'CheckCircle2']))} }} from 'lucide-react';
import FloatingWhatsAppButton from '@/Components/FloatingWhatsAppButton';
import {{ Button }} from '@/Components/ui/button';
import gsap from 'gsap';
import {{ ScrollTrigger }} from 'gsap/ScrollTrigger';
import {{ useGSAP }} from '@gsap/react';
import {{ __ }} from '@/lib/i18n';

gsap.registerPlugin(ScrollTrigger);

export default function {component_name}({{ auth }}) {{
    const mainRef = useRef(null);
    const phoneNumber = "201015218548";

    useGSAP(() => {{
        const sections = gsap.utils.toArray('.reveal-section');
        sections.forEach((section) => {{
            const elements = section.querySelectorAll('.gsap-fade-up');
            gsap.fromTo(elements, 
                {{ opacity: 0, y: 30 }},
                {{
                    opacity: 1, 
                    y: 0, 
                    duration: 0.8,
                    stagger: 0.1,
                    ease: "power2.out",
                    scrollTrigger: {{
                        trigger: section,
                        start: "top 85%",
                        toggleActions: "play none none reverse"
                    }}
                }}
            );
        }});
    }}, {{ scope: mainRef }});

    const openWhatsApp = (msg) => {{
        const encodedMessage = encodeURIComponent(msg);
        window.open(`https://wa.me/${{phoneNumber}}?text=${{encodedMessage}}`, '_blank');
    }};

    const features = ['''
    
    for i, f in enumerate(features):
        bullets_str = ', '.join([f'`{{__(\\'{lang_key}.feature_{i+1}_bullet_{j+1}\\', {{}}, \\'{b}\\')}}`' for j, b in enumerate(f['bullets'])])
        imports += f'''
        {{
            title: __('{lang_key}.feature_{i+1}_title', {{}}, '{f['title']}'),
            icon: {f['icon']},
            desc: __('{lang_key}.feature_{i+1}_desc', {{}}, '{f['desc']}'),
            bullets: [{bullets_str}]
        }},'''
    
    imports += f'''
    ];

    return (
        <PublicLayout auth={{auth}}>
            <Head>
                <title>{{__('{lang_key}.page_title', {{}}, '{page_title}')}}</title>
                <meta name="description" content={{__('{lang_key}.meta_desc', {{}}, '{meta_desc}')}} />
            </Head>

            <FloatingWhatsAppButton phoneNumber={{phoneNumber}} defaultMessage={{__('{lang_key}.whatsapp_msg', {{}}, '{whatsapp_msg}')}} />

            <div ref={{mainRef}} className="w-full bg-[#fcfcfc] text-[#111111] font-sans selection:bg-[#111111] selection:text-white overflow-x-hidden">
                
                {{/* Hero Section */}}
                <section className="pt-32 pb-24 lg:pt-48 lg:pb-32 px-6 lg:px-8 max-w-7xl mx-auto reveal-section border-b border-[#e5e5e5]">
                    <div className="max-w-4xl">
                        <div className="gsap-fade-up inline-flex items-center gap-2 px-3 py-1 border border-[#e5e5e5] text-xs font-semibold text-[#666666] tracking-widest uppercase mb-8 bg-white">
                            <span className="flex h-1.5 w-1.5 rounded-full bg-[#111111]"></span>
                            {{__('{lang_key}.category', {{}}, '{category}')}}
                        </div>
                        <h1 className="gsap-fade-up text-5xl lg:text-7xl font-extrabold text-[#111111] tracking-tight leading-[1.05] mb-6">
                            {{__('{lang_key}.hero_title', {{}}, '{hero_title}')}}
                        </h1>
                        <p className="gsap-fade-up text-xl text-[#666666] font-normal leading-relaxed max-w-2xl mb-10">
                            {{__('{lang_key}.hero_desc', {{}}, '{hero_desc}')}}
                        </p>
                        <Button 
                            onClick={{() => openWhatsApp(__('{lang_key}.whatsapp_msg', {{}}, '{whatsapp_msg}'))}}
                            className="gsap-fade-up bg-[#111111] text-white hover:bg-[#333333] rounded-xl px-8 py-6 text-sm font-bold uppercase tracking-wide transition-all"
                        >
                            {{__('{lang_key}.discuss_btn', {{}}, '{cta_btn}')}}
                        </Button>
                    </div>
                </section>

                {{/* Features Grid */}}
                <section className="py-24 px-6 lg:px-8 max-w-7xl mx-auto reveal-section">
                    <div className="text-center mb-16">
                        <h2 className="gsap-fade-up text-4xl font-extrabold mb-4">{{__('{lang_key}.features_title', {{}}, 'Core Capabilities')}}</h2>
                        <p className="gsap-fade-up text-lg text-[#666666]">{{__('{lang_key}.features_subtitle', {{}}, 'Engineered for reliability and scale.')}}</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {{features.map((feature, idx) => (
                            <div key={{idx}} className="gsap-fade-up bg-white p-8 lg:p-10 border border-[#e5e5e5] rounded-2xl shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] hover:border-[#111111] transition-all flex flex-col h-full group">
                                <div className="w-14 h-14 bg-[#f4f4f5] group-hover:bg-[#111111] transition-colors rounded-xl flex items-center justify-center mb-8">
                                    <feature.icon className="w-6 h-6 text-[#111111] group-hover:text-white transition-colors" />
                                </div>
                                <h3 className="text-2xl font-bold mb-4">{{feature.title}}</h3>
                                <p className="text-[#666666] leading-relaxed text-[15px] mb-8 flex-grow">
                                    {{feature.desc}}
                                </p>
                                <ul className="space-y-3 pt-6 border-t border-[#f4f4f5]">
                                    {{feature.bullets.map((bullet, i) => (
                                        <li key={{i}} className="flex items-center gap-3 text-[#444444]">
                                            <CheckCircle2 className="w-4 h-4 text-[#111111]" />
                                            <span className="text-sm font-medium" dangerouslySetInnerHTML={{{{ __html: bullet }}}} />
                                        </li>
                                    ))}}
                                </ul>
                            </div>
                        ))}}
                    </div>
                </section>

                {{/* CTA Section */}}
                <section className="py-32 bg-[#111111] text-white text-center reveal-section px-6">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="gsap-fade-up text-4xl md:text-5xl font-extrabold mb-6">
                            {{__('{lang_key}.cta_title', {{}}, '{cta_title}')}}
                        </h2>
                        <p className="gsap-fade-up text-xl text-[#a3a3a3] mb-12 leading-relaxed">
                            {{__('{lang_key}.cta_desc', {{}}, '{cta_desc}')}}
                        </p>
                        <Button 
                            onClick={{() => openWhatsApp(__('{lang_key}.whatsapp_msg', {{}}, '{whatsapp_msg}'))}}
                            className="gsap-fade-up bg-white text-[#111111] hover:bg-[#e5e5e5] rounded-xl px-10 py-7 text-sm font-bold tracking-wide uppercase transition-all flex items-center justify-center gap-3 mx-auto"
                        >
                            {{__('{lang_key}.cta_btn', {{}}, '{cta_btn}')}} <ArrowRight className="w-4 h-4" />
                        </Button>
                    </div>
                </section>

            </div>
        </PublicLayout>
    );
}}
'''
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(imports)

pages = [
    {
        'path': os.path.join(js_dir, 'Platforms', 'Cloud.jsx'),
        'component_name': 'Cloud',
        'lang_key': 'landing_platforms.cloud',
        'page_title': 'Cloud Infrastructure | Musoftware',
        'meta_desc': 'Scalable, secure, and fully managed cloud hosting.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss Cloud Infrastructure.',
        'category': 'Platform',
        'hero_title': 'Cloud Infrastructure.',
        'hero_desc': 'Scalable, secure, and fully managed cloud hosting. We provide the robust architecture required to run enterprise applications without downtime.',
        'cta_title': 'Ready to scale your architecture?',
        'cta_desc': 'Stop worrying about server maintenance. Let us manage your cloud infrastructure.',
        'cta_btn': 'Book a Consultation',
        'features': [
            {'title': 'High Availability', 'icon': 'Activity', 'desc': 'Load-balanced servers ensuring 99.9% uptime for mission-critical software. Traffic is seamlessly routed to healthy nodes.', 'bullets': ['Auto-scaling groups', 'Failover routing', 'Global edge network']},
            {'title': 'Automated Backups', 'icon': 'Server', 'desc': 'Secure, off-site daily snapshots protect your data against catastrophic failure. Instantly restore databases to any point in time.', 'bullets': ['Encrypted snapshots', 'Point-in-time recovery', 'Geographic redundancy']},
            {'title': 'Isolated Environments', 'icon': 'Shield', 'desc': 'Dedicated resources and private networks for maximum security compliance. Your data never shares space with public instances.', 'bullets': ['Virtual Private Cloud', 'Strict firewall rules', 'DDoS mitigation']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Platforms', 'Crm.jsx'),
        'component_name': 'Crm',
        'lang_key': 'landing_platforms.crm',
        'page_title': 'MU CRM - Customer Operations Platform',
        'meta_desc': 'A centralized infrastructure for managing customer data, tracking interactions, and accelerating sales cycles.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss a CRM Platform.',
        'category': 'Platform',
        'hero_title': 'Customer Operations Platform.',
        'hero_desc': 'A centralized infrastructure for managing customer data, tracking interactions, and accelerating sales cycles. Built for scale and operational clarity.',
        'cta_title': 'Ready to accelerate your sales?',
        'cta_desc': 'Stop using spreadsheets. Upgrade to a fully integrated CRM system today.',
        'cta_btn': 'Contact Sales',
        'features': [
            {'title': 'Centralized Data', 'icon': 'Server', 'desc': 'Single source of truth for all client interactions, eliminating data silos.', 'bullets': ['Unified client profiles', 'Interaction history', 'Document storage']},
            {'title': 'Pipeline Management', 'icon': 'Activity', 'desc': 'Clear visibility into sales cycles, conversion rates, and operational bottlenecks.', 'bullets': ['Custom sales stages', 'Probability forecasting', 'Kanban views']},
            {'title': 'Automated Workflows', 'icon': 'Shield', 'desc': 'Reduce manual entry with automated lead assignment and follow-up triggers.', 'bullets': ['Lead routing', 'Email sequences', 'Task reminders']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Platforms', 'Erp.jsx'),
        'component_name': 'Erp',
        'lang_key': 'landing_platforms.erp',
        'page_title': 'MU ERP - Enterprise Resource Planning',
        'meta_desc': 'Manage your entire business operations with a unified enterprise resource planning system.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss an ERP Platform.',
        'category': 'Platform',
        'hero_title': 'Enterprise Resource Planning.',
        'hero_desc': 'Connect your financials, supply chain, operations, commerce, and HR on a unified platform that scales with your enterprise.',
        'cta_title': 'Ready to unify your operations?',
        'cta_desc': 'Eliminate fragmented systems and get full visibility into your enterprise today.',
        'cta_btn': 'Contact Sales',
        'features': [
            {'title': 'Financial Management', 'icon': 'Activity', 'desc': 'Automate your financial processes and gain real-time insights into your business performance.', 'bullets': ['General ledger', 'Accounts payable/receivable', 'Multi-currency']},
            {'title': 'Supply Chain & Inventory', 'icon': 'Server', 'desc': 'Optimize inventory levels, streamline procurement, and improve fulfillment processes.', 'bullets': ['Real-time tracking', 'Automated reordering', 'Warehouse management']},
            {'title': 'Human Resources', 'icon': 'Shield', 'desc': 'Manage your workforce efficiently with integrated payroll, benefits, and performance tracking.', 'bullets': ['Payroll processing', 'Time & attendance', 'Employee self-service']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Solutions', 'Ecommerce.jsx'),
        'component_name': 'Ecommerce',
        'lang_key': 'landing_solutions.ecommerce',
        'page_title': 'E-commerce Solutions | Musoftware',
        'meta_desc': 'Robust e-commerce infrastructure designed for high traffic and complex inventory needs.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss an E-commerce Solution.',
        'category': 'Solution',
        'hero_title': 'High-Volume Retail.',
        'hero_desc': 'Robust e-commerce infrastructure designed for high traffic and complex inventory needs. Scale your retail operations globally without performance bottlenecks.',
        'cta_title': 'Ready to scale your store?',
        'cta_desc': 'Stop wrestling with basic templates. Build an e-commerce engine that handles millions of requests.',
        'cta_btn': 'Book a Consultation',
        'features': [
            {'title': 'Inventory Synchronization', 'icon': 'Globe', 'desc': 'Real-time stock updates across multiple warehouses and sales channels, preventing overselling.', 'bullets': ['Multi-location stock', 'Low inventory alerts', 'Automated POs']},
            {'title': 'Payment Processing', 'icon': 'CreditCard', 'desc': 'Secure, multi-currency payment gateways with advanced fraud detection systems to maximize conversions.', 'bullets': ['Stripe / Paymob / PayPal', 'Dynamic currency formatting', 'Saved cards']},
            {'title': 'Order Fulfillment', 'icon': 'ShoppingCart', 'desc': 'Automate shipping logic, tracking generation, and return management workflows to speed up delivery.', 'bullets': ['Carrier API integration', 'Automated tracking emails', 'Return workflows']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Solutions', 'Education.jsx'),
        'component_name': 'Education',
        'lang_key': 'landing_solutions.education',
        'page_title': 'Education Software Solutions | Musoftware',
        'meta_desc': 'Comprehensive systems for schools and universities. Centralize student information, manage admissions, and facilitate e-learning.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss an Education Software Solution.',
        'category': 'Solution',
        'hero_title': 'Digital Campus Platforms.',
        'hero_desc': 'Comprehensive systems for schools and universities. Centralize student information, manage admissions, and facilitate e-learning environments.',
        'cta_title': 'Ready to digitize your campus?',
        'cta_desc': 'Provide a seamless experience for students, parents, and faculty with our unified platforms.',
        'cta_btn': 'Discuss Education Needs',
        'features': [
            {'title': 'Student Information System', 'icon': 'Server', 'desc': 'Manage academic records, enrollment, and grading in one unified database.', 'bullets': ['Digital enrollment', 'Gradebook management', 'Attendance tracking']},
            {'title': 'E-Learning Portals', 'icon': 'Globe', 'desc': 'Interactive platforms for assignments, virtual classrooms, and course material distribution.', 'bullets': ['Video integrations', 'Online assignments', 'Discussion boards']},
            {'title': 'Administration & HR', 'icon': 'Activity', 'desc': 'Automate faculty payroll, resource scheduling, and institutional reporting.', 'bullets': ['Staff scheduling', 'Payroll automation', 'Compliance reporting']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Solutions', 'Finance.jsx'),
        'component_name': 'Finance',
        'lang_key': 'landing_solutions.finance',
        'page_title': 'Financial Software Solutions | Musoftware',
        'meta_desc': 'Secure, compliant, and highly performant financial software for banks, fintech startups, and investment firms.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss a Financial Software Solution.',
        'category': 'Solution',
        'hero_title': 'Fintech & Banking Infrastructure.',
        'hero_desc': 'Secure, compliant, and highly performant financial software for banks, fintech startups, and investment firms. Built for extreme reliability.',
        'cta_title': 'Ready to upgrade your financial tech?',
        'cta_desc': 'Build secure, scalable financial products that your customers can trust.',
        'cta_btn': 'Discuss Finance Needs',
        'features': [
            {'title': 'Secure Transactions', 'icon': 'Shield', 'desc': 'Bank-grade encryption and compliance frameworks for secure money movement and storage.', 'bullets': ['PCI-DSS compliance', 'End-to-end encryption', 'Fraud detection']},
            {'title': 'Ledger & Accounting', 'icon': 'Activity', 'desc': 'Immutable ledger systems for accurate financial tracking, auditing, and reporting.', 'bullets': ['Double-entry bookkeeping', 'Automated reconciliation', 'Audit trails']},
            {'title': 'Client Portals', 'icon': 'Globe', 'desc': 'Intuitive dashboards for clients to monitor portfolios, transfer funds, and access statements.', 'bullets': ['Real-time analytics', 'Mobile-first design', 'Self-service tools']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Solutions', 'Healthcare.jsx'),
        'component_name': 'Healthcare',
        'lang_key': 'landing_solutions.healthcare',
        'page_title': 'Healthcare Software Solutions | Musoftware',
        'meta_desc': 'HIPAA-compliant platforms for clinics and hospitals. Streamline patient records, billing, and telehealth.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss a Healthcare Software Solution.',
        'category': 'Solution',
        'hero_title': 'Medical Management Systems.',
        'hero_desc': 'HIPAA-compliant platforms for clinics and hospitals. Streamline patient records, billing, and telehealth services in one secure environment.',
        'cta_title': 'Ready to modernize your clinic?',
        'cta_desc': 'Improve patient care and operational efficiency with our secure healthcare platforms.',
        'cta_btn': 'Discuss Healthcare Needs',
        'features': [
            {'title': 'Electronic Health Records', 'icon': 'Server', 'desc': 'Securely store and manage patient histories, lab results, and treatment plans.', 'bullets': ['HIPAA compliance', 'Secure access controls', 'Interoperability standards']},
            {'title': 'Practice Management', 'icon': 'Activity', 'desc': 'Streamline appointment scheduling, resource allocation, and clinical workflows.', 'bullets': ['Smart scheduling', 'Automated reminders', 'Staff optimization']},
            {'title': 'Medical Billing', 'icon': 'Globe', 'desc': 'Automate insurance claims processing, patient invoicing, and revenue cycle management.', 'bullets': ['Insurance integration', 'Payment gateways', 'Financial reporting']}
        ]
    },
    {
        'path': os.path.join(js_dir, 'Solutions', 'RealEstate.jsx'),
        'component_name': 'RealEstate',
        'lang_key': 'landing_solutions.realestate',
        'page_title': 'Real Estate Software Solutions | Musoftware',
        'meta_desc': 'Comprehensive property management and brokerage platforms. Manage listings, tenants, and contracts.',
        'whatsapp_msg': 'Hello Mahmoud, I want to discuss a Real Estate Software Solution.',
        'category': 'Solution',
        'hero_title': 'Property Management Platforms.',
        'hero_desc': 'Comprehensive property management and brokerage platforms. Manage listings, tenants, maintenance requests, and financial contracts effortlessly.',
        'cta_title': 'Ready to streamline your property management?',
        'cta_desc': 'Manage thousands of properties and tenants without the administrative overhead.',
        'cta_btn': 'Discuss Real Estate Needs',
        'features': [
            {'title': 'Listing Management', 'icon': 'Globe', 'desc': 'Centralized database for all properties, integrated with major real estate portals.', 'bullets': ['Multi-portal publishing', 'High-res media galleries', 'Virtual tours']},
            {'title': 'Tenant Portals', 'icon': 'Server', 'desc': 'Self-service dashboards for tenants to pay rent, submit maintenance requests, and view leases.', 'bullets': ['Online rent collection', 'Ticketing system', 'Document signing']},
            {'title': 'Brokerage Operations', 'icon': 'Activity', 'desc': 'Track commissions, agent performance, and lead pipelines in a unified CRM.', 'bullets': ['Commission tracking', 'Lead routing', 'Performance analytics']}
        ]
    }
]

for page in pages:
    create_premium_file(**page)

print('Generated 8 component files.')
