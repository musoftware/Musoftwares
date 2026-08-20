import React from 'react';

interface StudioHeaderProps {
    badge?: string;
    title: React.ReactNode;
    subtitle?: string;
    className?: string;
}

export default function StudioHeader({
    badge,
    title,
    subtitle,
    className = '',
}: StudioHeaderProps) {
    return (
        <section className={`px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-16 sm:mb-24 ${className}`}>
            {badge && (
                <span className="text-xs font-mono uppercase tracking-[0.2em] rtl:tracking-normal rtl:normal-case text-[#748660] font-bold mb-4 inline-block">
                    {badge}
                </span>
            )}
            <h1 className="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-white max-w-4xl mb-6 tracking-tight font-sans">
                {title}
            </h1>
            {subtitle && (
                <p className="text-base sm:text-lg text-zinc-400 max-w-2xl font-sans font-normal leading-relaxed">
                    {subtitle}
                </p>
            )}
        </section>
    );
}
