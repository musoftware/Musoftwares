import re

file_path = r"d:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\resources\js\Pages\Public\Home.jsx"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update Portfolio Items to include case study fields
# We'll replace the existing portfolioItems array with a new one that maps the fields.
portfolio_target = "const portfolioItems = ["
# Find where portfolioItems ends
end_idx = content.find("];", content.find(portfolio_target)) + 2
old_portfolio = content[content.find(portfolio_target):end_idx]

# We will inject a modified portfolio items definition. Instead of manually editing the string, 
# we'll regex replace the item objects to inject challenge, solution, tech.

def replacer(match):
    # match is the whole object string
    inner = match.group(1)
    # Add fields before the closing brace
    new_fields = ", challenge: 'Faced with operational inefficiencies and scattered data, the client needed a unified system.', solution: 'We built a comprehensive, scalable platform tailored to their workflow.', techs: ['React', 'Laravel', 'PostgreSQL']"
    return "{" + inner + new_fields + "}"

new_portfolio = re.sub(r'\{([^}]+)\}', replacer, old_portfolio)

content = content.replace(old_portfolio, new_portfolio)

# 2. Add Client Logos section (Trusted By) below the Hero Section or above Testimonials.
# I'll put it above the PROBLEM SECTION.

problem_target = "{/* PROBLEM SECTION */}"
client_logos_html = """{/* CLIENT LOGOS SECTION */}
            <section className="py-12 bg-white border-t border-slate-100">
                <div className="max-w-[90rem] mx-auto px-6 lg:px-8">
                    <p className="text-center text-sm font-semibold text-slate-400 uppercase tracking-widest mb-8">
                        {__('general.landing_trusted_by')}
                    </p>
                    <div className="flex flex-wrap justify-center gap-12 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                        {/* Placeholder logos */}
                        <div className="flex items-center gap-2 font-bold text-xl text-slate-800"><Box className="w-8 h-8" /> TechCorp</div>
                        <div className="flex items-center gap-2 font-bold text-xl text-slate-800"><Server className="w-8 h-8" /> DataSys</div>
                        <div className="flex items-center gap-2 font-bold text-xl text-slate-800"><Monitor className="w-8 h-8" /> ScreenInc</div>
                        <div className="flex items-center gap-2 font-bold text-xl text-slate-800"><Zap className="w-8 h-8" /> FastLogistics</div>
                        <div className="flex items-center gap-2 font-bold text-xl text-slate-800"><Shield className="w-8 h-8" /> SecureNet</div>
                    </div>
                </div>
            </section>

            {/* PROBLEM SECTION */}"""

content = content.replace(problem_target, client_logos_html)

# 3. Update the Modal JSX in Home.jsx
# We need to find the <motion.div> inside {selectedItem && ( ... )} that renders the details.
modal_content_target = """                            <div className="p-6 md:p-8 bg-slate-900 border-t border-slate-800 text-left">
                                <span className="text-emerald-400 text-sm font-semibold tracking-wider uppercase mb-2 block">
                                    {selectedItem.cat}
                                </span>
                                <h3 className="text-2xl md:text-3xl font-bold text-white mb-3">
                                    {__(`general.${selectedItem.titleKey}`)}
                                </h3>
                                <p className="text-slate-400 text-base md:text-lg max-w-3xl leading-relaxed">
                                    {__(`general.${selectedItem.descKey}`)}
                                </p>
                            </div>"""

modal_content_replacement = """                            <div className="p-6 md:p-8 bg-slate-900 border-t border-slate-800 text-left overflow-y-auto max-h-[50vh]">
                                <span className="text-emerald-400 text-sm font-semibold tracking-wider uppercase mb-2 block">
                                    {selectedItem.cat}
                                </span>
                                <h3 className="text-2xl md:text-3xl font-bold text-white mb-3">
                                    {__(`general.${selectedItem.titleKey}`)}
                                </h3>
                                <p className="text-slate-400 text-base md:text-lg max-w-3xl leading-relaxed mb-8">
                                    {__(`general.${selectedItem.descKey}`)}
                                </p>
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-t border-slate-800 pt-8">
                                    <div>
                                        <h4 className="text-lg font-bold text-white mb-3 flex items-center gap-2">
                                            <Box className="w-5 h-5 text-rose-400" /> {__('general.landing_portfolio_challenge')}
                                        </h4>
                                        <p className="text-slate-400 leading-relaxed text-sm">
                                            {selectedItem.challenge}
                                        </p>
                                    </div>
                                    <div>
                                        <h4 className="text-lg font-bold text-white mb-3 flex items-center gap-2">
                                            <Zap className="w-5 h-5 text-emerald-400" /> {__('general.landing_portfolio_solution')}
                                        </h4>
                                        <p className="text-slate-400 leading-relaxed text-sm">
                                            {selectedItem.solution}
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="border-t border-slate-800 pt-6">
                                    <h4 className="text-sm font-bold text-slate-300 mb-4 uppercase tracking-wider">
                                        {__('general.landing_portfolio_technologies')}
                                    </h4>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedItem.techs && selectedItem.techs.map((tech, i) => (
                                            <span key={i} className="px-3 py-1 bg-slate-800 border border-slate-700 text-slate-300 text-xs font-semibold rounded-full">
                                                {tech}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>"""

content = content.replace(modal_content_target, modal_content_replacement)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Home.jsx updated successfully for Sprint 2.")
