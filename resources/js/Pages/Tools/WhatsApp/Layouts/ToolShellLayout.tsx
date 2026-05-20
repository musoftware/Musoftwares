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
            className={`min-h-screen bg-[#F8FAFC] text-slate-900 font-sans selection:bg-teal-500 selection:text-white flex flex-col md:flex-row transition-all duration-300 ${isRtl ? 'text-right' : 'text-left'}`}
        >
            {/* Sidebar & Bottom Nav Rendered via Sidebar Component */}
            {sidebar}
            
            {/* Main Content */}
            <main className="flex-1 flex flex-col min-h-screen overflow-x-hidden mb-20 md:mb-0">
                <div className="flex-1 p-4 md:p-8 max-w-6xl mx-auto w-full animate-in fade-in duration-300">
                    {children}
                </div>
            </main>
        </div>
    );
}
