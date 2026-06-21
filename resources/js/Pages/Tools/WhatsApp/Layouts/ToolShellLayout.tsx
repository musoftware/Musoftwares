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
            className={`min-h-screen bg-background text-foreground font-sans selection:bg-teal-500 selection:text-white flex flex-col md:flex-row transition-all duration-300 ${isRtl ? 'text-end' : 'text-start'} relative overflow-hidden`}
        >
            {/* Ambient Premium Background */}
            <div className="absolute top-0 start-0 w-[500px] h-[500px] bg-teal-400/10 rounded-full blur-[120px] -z-10 animate-pulse pointer-events-none" style={{ animationDuration: '8s' }} />
            <div className="absolute bottom-0 end-0 w-[600px] h-[600px] bg-indigo-500/5 rounded-full blur-[150px] -z-10 pointer-events-none" />

            {/* Sidebar & Bottom Nav Rendered via Sidebar Component */}
            {sidebar}
            
            {/* Main Content */}
            <main className="flex-1 flex flex-col min-h-screen overflow-x-hidden mb-20 md:mb-0 relative z-10">
                <div className="flex-1 p-4 md:p-10 max-w-7xl mx-auto w-full animate-in fade-in zoom-in-95 duration-500">
                    {children}
                </div>
            </main>
        </div>
    );
}
