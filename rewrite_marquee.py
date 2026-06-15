import re

file_path = r"d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\resources\js\Pages\Public\Home.jsx"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Add clientsData array
clients_data_code = """const clientsData = [
    { name: "AMC Academy", logo: "/images/clients/amcacademy.png" },
    { name: "Aswan", logo: "/images/clients/aswan.png" },
    { name: "Egy Servers", logo: "/images/clients/egy-servers.png" },
    { name: "Jad Technology", logo: "/images/clients/jad-technology.png" },
    { name: "Mini Fatora", logo: "/images/clients/mini-fatora.png" },
    { name: "MIT", logo: "/images/clients/mit.png" },
    { name: "My Line", logo: "/images/clients/my-line.png" },
    { name: "OBD Ultra", logo: "/images/clients/obdultra.png" },
    { name: "Technosoft", logo: "/images/clients/technosoft.png" },
    { name: "Topline", logo: "/images/clients/topline.png" },
];

"""

statistics_target = "];\n\nconst fadeUp = {"
content = content.replace("];\n\nconst fadeUp = {", "];\n\n" + clients_data_code + "const fadeUp = {")

# 2. Replace CLIENT LOGOS SECTION

old_client_logos = """            {/* CLIENT LOGOS SECTION */}
            <section className="py-12 bg-white border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <p className="text-center text-sm font-semibold text-slate-400 uppercase tracking-widest mb-8">
                        {__('general.landing_trusted_by')}
                    </p>
                    <div className="flex flex-wrap justify-center items-center gap-12 opacity-60 grayscale hover:grayscale-0 transition-all duration-500">
                        <img src="/images/clients/amcacademy.png" alt="AMC Academy" className="h-10 md:h-12 object-contain" />
                        <img src="/images/clients/egy-servers.png" alt="Egy Servers" className="h-10 md:h-12 object-contain" />
                        <img src="/images/clients/jad-technology.png" alt="Jad Technology" className="h-10 md:h-12 object-contain" />
                        <img src="/images/clients/mini-fatora.png" alt="Mini Fatora" className="h-10 md:h-12 object-contain" />
                        <img src="/images/clients/technosoft.png" alt="Technosoft" className="h-10 md:h-12 object-contain" />
                        <img src="/images/clients/obdultra.png" alt="OBD Ultra" className="h-10 md:h-12 object-contain" />
                    </div>
                </div>
            </section>"""

new_client_logos = """            {/* CLIENT LOGOS SECTION */}
            <section className="py-12 bg-white border-t border-slate-100 overflow-hidden">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <p className="text-center text-sm font-semibold text-slate-400 uppercase tracking-widest mb-8">
                        {__('general.landing_trusted_by')}
                    </p>
                    
                    {/* Marquee Container */}
                    <div className="relative flex overflow-x-hidden group">
                        <motion.div
                            className="flex items-center gap-12 sm:gap-24 pr-12 sm:pr-24"
                            animate={{ x: ["0%", "-50%"] }}
                            transition={{ repeat: Infinity, ease: "linear", duration: 25 }}
                        >
                            {/* We render the array twice to create a seamless infinite loop */}
                            {[...clientsData, ...clientsData].map((client, index) => (
                                <img 
                                    key={`client-${index}`} 
                                    src={client.logo} 
                                    alt={client.name} 
                                    className="h-10 md:h-12 w-auto object-contain shrink-0 opacity-50 grayscale hover:grayscale-0 transition-all duration-300" 
                                />
                            ))}
                        </motion.div>
                    </div>
                </div>
            </section>"""

content = content.replace(old_client_logos, new_client_logos)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Home.jsx updated with marquee.")
