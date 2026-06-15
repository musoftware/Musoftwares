import re

file_path = r"d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\resources\js\Pages\Public\Home.jsx"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update imports
imports_target = """import { 
    Monitor, Smartphone, Server, CheckCircle, 
    ArrowRight, LayoutDashboard, Ticket, FolderKanban, X,
    Users, MessageSquare, TrendingUp, Calendar, Store, Wrench,
    Download, MessageCircle, Search, Box
} from 'lucide-react';"""

imports_replacement = """import { 
    Monitor, Smartphone, Server, CheckCircle, 
    ArrowRight, LayoutDashboard, Ticket, FolderKanban, X,
    Users, MessageSquare, TrendingUp, Calendar, Store, Wrench,
    Download, MessageCircle, Search, Box, Star, Quote, Zap, Shield, Rocket, ArrowUpRight, BarChart
} from 'lucide-react';

const testimonialsData = [
    { name: 'Ahmed Hassan', company: 'TechVision', text: 'MuSoftwares completely transformed how we handle our internal operations. The ERP system is intuitive and powerful.', rating: 5 },
    { name: 'Sara El-Sayed', company: 'RetailPro', text: 'The custom POS and inventory system they built for us saved us hundreds of hours every month. Highly recommended.', rating: 5 },
    { name: 'Mohamed Ali', company: 'Global Logistics', text: 'Excellent team and unparalleled support. Their SaaS solutions are reliable and scale perfectly with our business growth.', rating: 5 },
    { name: 'Omar Youssef', company: 'StartUp Inc', text: 'They delivered our platform ahead of schedule with exceptional quality. Their attention to detail is truly impressive.', rating: 5 },
];

const statisticsData = [
    { value: '500+', label: 'Projects Delivered' },
    { value: '99%', label: 'Client Satisfaction' },
    { value: '24/7', label: 'Support Available' },
    { value: '50+', label: 'Enterprise Clients' }
];"""

content = content.replace(imports_target, imports_replacement)

# 2. Update state variables and useEffect
state_target = """    const [selectedItem, setSelectedItem] = useState(null);
    const carouselRef = useRef(null);
    const [width, setWidth] = useState(0);
    const { website_services, locale } = usePage().props;

    useEffect(() => {
        if (carouselRef.current) {
            setWidth(carouselRef.current.scrollWidth - carouselRef.current.offsetWidth);
        }
    }, []);"""

state_replacement = """    const [selectedItem, setSelectedItem] = useState(null);
    const carouselRef = useRef(null);
    const testimonialsRef = useRef(null);
    const [width, setWidth] = useState(0);
    const [testiWidth, setTestiWidth] = useState(0);
    const { website_services, locale } = usePage().props;

    useEffect(() => {
        if (carouselRef.current) {
            setWidth(carouselRef.current.scrollWidth - carouselRef.current.offsetWidth);
        }
        if (testimonialsRef.current) {
            setTestiWidth(testimonialsRef.current.scrollWidth - testimonialsRef.current.offsetWidth);
        }
    }, [carouselRef.current, testimonialsRef.current]);"""

content = content.replace(state_target, state_replacement)

# 3. Hero & Problem
hero_target = """                            <motion.div variants={fadeUp} className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Button 
                                    onClick={() => window.dispatchEvent(new Event('open-guest-ticket'))}
                                    size="lg" 
                                    className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all"
                                >
                                    {__('general.submit_guest_ticket') || 'Submit Guest Ticket'}
                                </Button>
                                <Link href="/platforms">
                                    <Button size="lg" variant="outline" className="w-full sm:w-auto hover:bg-slate-50 text-slate-900 border-slate-200 rounded-full px-10 h-14 text-base font-semibold transition-all">
                                        {__('general.landing_hero_secondary_cta')}
                                    </Button>
                                </Link>
                            </motion.div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* SERVICES SECTION */}"""

hero_replacement = """                            <motion.div variants={fadeUp} className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Button 
                                    onClick={() => window.dispatchEvent(new Event('open-guest-ticket'))}
                                    size="lg" 
                                    className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white rounded-full px-10 h-14 text-base font-semibold transition-all shadow-lg hover:shadow-xl"
                                >
                                    {__('general.submit_guest_ticket') || 'Submit Guest Ticket'} <ArrowRight className="ml-2 w-5 h-5" />
                                </Button>
                                <Link href="/platforms">
                                    <Button size="lg" variant="outline" className="w-full sm:w-auto hover:bg-slate-50 text-slate-900 border-slate-200 rounded-full px-10 h-14 text-base font-semibold transition-all">
                                        {__('general.landing_hero_secondary_cta')}
                                    </Button>
                                </Link>
                            </motion.div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* PROBLEM SECTION */}
            <section className="py-24 lg:py-32 bg-slate-50 relative border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-16 text-center max-w-3xl mx-auto">
                        <p className="text-sm font-semibold text-rose-500 uppercase tracking-widest mb-4">
                            {__('general.landing_problem_badge')}
                        </p>
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('general.landing_problem_title')}
                        </h2>
                        <p className="text-xl text-slate-500 font-light leading-relaxed">
                            {__('general.landing_problem_desc')}
                        </p>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-200 hover:shadow-md transition-all duration-300">
                            <div className="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mb-6">
                                <Server className="w-6 h-6 text-rose-500" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">Fragmented Tools</h3>
                            <p className="text-slate-500 leading-relaxed">Using different platforms for CRM, accounting, and task management creates data silos.</p>
                        </div>
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-200 hover:shadow-md transition-all duration-300">
                            <div className="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center mb-6">
                                <Monitor className="w-6 h-6 text-amber-500" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">Manual Processes</h3>
                            <p className="text-slate-500 leading-relaxed">Wasting hundreds of hours on manual data entry and repetitive administrative tasks.</p>
                        </div>
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-blue-200 hover:shadow-md transition-all duration-300">
                            <div className="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-6">
                                <BarChart className="w-6 h-6 text-blue-500" />
                            </div>
                            <h3 className="text-xl font-bold text-slate-900 mb-3">Lack of Visibility</h3>
                            <p className="text-slate-500 leading-relaxed">Unable to make quick decisions due to scattered metrics and delayed reporting.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* SERVICES SECTION */}"""

content = content.replace(hero_target, hero_replacement)

# 4. Change SERVICES SECTION bg to bg-white
services_bg_target = """{/* SERVICES SECTION */}
            <section id="services" className="py-24 lg:py-32 bg-slate-50 relative border-y border-slate-100">"""

services_bg_replacement = """{/* SERVICES SECTION */}
            <section id="services" className="py-24 lg:py-32 bg-white relative border-t border-slate-100">"""

content = content.replace(services_bg_target, services_bg_replacement)

# 5. Benefits & Statistics
benefits_target = """                </div>
            </section>

            {/* PORTFOLIO SECTION */}"""

benefits_replacement = """                </div>
            </section>

            {/* BENEFITS SECTION */}
            <section className="py-24 lg:py-32 bg-white relative border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="mb-16 text-center max-w-3xl mx-auto">
                        <p className="text-sm font-semibold text-emerald-600 uppercase tracking-widest mb-4">
                            {__('general.landing_benefits_badge')}
                        </p>
                        <h2 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-6">
                            {__('general.landing_benefits_title')}
                        </h2>
                        <p className="text-xl text-slate-500 font-light leading-relaxed">
                            {__('general.landing_benefits_desc')}
                        </p>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        <div className="space-y-8">
                            <div className="flex gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                    <Zap className="w-6 h-6 text-emerald-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">Automated Workflows</h3>
                                    <p className="text-slate-500 leading-relaxed">Eliminate repetitive tasks with intelligent automation that connects all your departments seamlessly.</p>
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                    <Shield className="w-6 h-6 text-blue-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">Enterprise Security</h3>
                                    <p className="text-slate-500 leading-relaxed">Bank-grade encryption, role-based access control, and continuous security monitoring for your data.</p>
                                </div>
                            </div>
                            <div className="flex gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                    <Rocket className="w-6 h-6 text-violet-500" />
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">Scalable Architecture</h3>
                                    <p className="text-slate-500 leading-relaxed">Built on a robust infrastructure that scales effortlessly from small teams to large enterprises.</p>
                                </div>
                            </div>
                        </div>
                        <div className="relative">
                            <div className="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 to-blue-500/10 rounded-3xl blur-3xl"></div>
                            <div className="relative bg-slate-900 rounded-3xl p-8 border border-slate-800 shadow-2xl">
                                <div className="flex items-center gap-3 mb-6 border-b border-slate-800 pb-4">
                                    <div className="w-3 h-3 rounded-full bg-rose-500"></div>
                                    <div className="w-3 h-3 rounded-full bg-amber-500"></div>
                                    <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
                                </div>
                                <div className="space-y-4">
                                    <div className="h-4 bg-slate-800 rounded-md w-3/4"></div>
                                    <div className="h-4 bg-slate-800 rounded-md w-full"></div>
                                    <div className="h-4 bg-slate-800 rounded-md w-5/6"></div>
                                    <div className="grid grid-cols-2 gap-4 mt-8">
                                        <div className="h-24 bg-slate-800/50 rounded-xl border border-slate-800"></div>
                                        <div className="h-24 bg-slate-800/50 rounded-xl border border-slate-800"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* STATISTICS SECTION */}
            <section className="py-20 bg-slate-900 relative border-t border-slate-800">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-slate-800/50">
                        {statisticsData.map((stat, i) => (
                            <div key={i} className="text-center px-4">
                                <div className="text-4xl md:text-5xl font-extrabold text-white mb-2 tracking-tight">
                                    {stat.value}
                                </div>
                                <div className="text-sm md:text-base font-medium text-slate-400 uppercase tracking-wider">
                                    {stat.label}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* PORTFOLIO SECTION */}"""

content = content.replace(benefits_target, benefits_replacement)

# 6. Testimonials
testi_target = """                </div>
            </section>

            {/* FINAL CTA / CONTACT */}"""

testi_replacement = """                </div>
            </section>

            {/* TESTIMONIALS SECTION */}
            <section className="py-24 lg:py-32 bg-slate-50 relative border-y border-slate-200 overflow-hidden">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 text-center mb-16">
                    <p className="text-sm font-semibold text-blue-600 uppercase tracking-widest mb-4">
                        {__('general.landing_testimonials_badge')}
                    </p>
                    <h2 className="text-4xl font-extrabold tracking-tight mb-4 text-slate-900">
                        {__('general.landing_testimonials_title')}
                    </h2>
                    <p className="text-xl text-slate-600 font-light max-w-2xl mx-auto">
                        {__('general.landing_testimonials_desc')}
                    </p>
                </div>

                <div className="max-w-[90rem] mx-auto px-6 lg:px-8 cursor-grab active:cursor-grabbing">
                    <motion.div ref={testimonialsRef} className="overflow-hidden">
                        <motion.div 
                            drag="x" 
                            dragConstraints={{ right: 0, left: -testiWidth }} 
                            className="flex gap-6"
                        >
                            {testimonialsData.map((testi, index) => (
                                <motion.div 
                                    key={index} 
                                    className="min-w-[320px] md:min-w-[400px] bg-white rounded-3xl p-8 border border-slate-200 shadow-sm flex flex-col hover:border-blue-200 transition-colors"
                                >
                                    <div className="flex gap-1 mb-6">
                                        {[...Array(testi.rating)].map((_, i) => (
                                            <Star key={i} className="w-5 h-5 text-amber-400 fill-amber-400" />
                                        ))}
                                    </div>
                                    <Quote className="w-10 h-10 text-slate-200 mb-4" />
                                    <p className="text-slate-600 text-lg italic leading-relaxed mb-8 flex-1">
                                        "{testi.text}"
                                    </p>
                                    <div className="flex items-center gap-4 mt-auto">
                                        <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-lg border border-slate-200">
                                            {testi.name.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 className="font-bold text-slate-900">{testi.name}</h4>
                                            <p className="text-sm text-slate-500">{testi.company}</p>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </motion.div>
                    </motion.div>
                </div>
            </section>

            {/* FINAL CTA / CONTACT */}"""

content = content.replace(testi_target, testi_replacement)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Home.jsx updated successfully.")
