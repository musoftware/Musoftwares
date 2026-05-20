import React from 'react';

interface ToolShellLayoutProps {
    children: React.ReactNode;
    sidebar: React.ReactNode;
    locale: 'en' | 'ar';
}

export default function ToolShellLayout({ children, sidebar, locale }: ToolShellLayoutProps) {
    const isRtl = locale === 'ar';
    
    return (
        <div 
            dir={isRtl ? 'rtl' : 'ltr'} 
            className={`min-h-screen bg-[#F8FAFC] text-slate-900 font-sans selection:bg-teal-500 selection:text-white flex transition-all duration-300 ${isRtl ? 'text-right' : 'text-left'}`}
        >
            {/* Sidebar */}
            <aside className="w-64 shrink-0 bg-white border-r border-slate-200 shadow-sm flex flex-col h-screen sticky top-0 z-20">
                {sidebar}
            </aside>
            
            {/* Main Content */}
            <main className="flex-1 flex flex-col min-h-screen overflow-x-hidden">
                <div className="flex-1 p-6 md:p-8 max-w-6xl mx-auto w-full animate-in fade-in duration-300">
                    {children}
                </div>
            </main>
        </div>
    );
}
