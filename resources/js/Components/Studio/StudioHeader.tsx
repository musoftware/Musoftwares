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
        <section className={`px-6 max-w-5xl mx-auto flex flex-col items-center text-center mb-12 sm:mb-16 ${className}`}>
            {badge && (
                <span className="text-[12px] font-medium tracking-normal text-[#1d1d1f]/80 bg-[#f5f5f7] border border-black/5 px-3.5 py-1 rounded-full mb-4 inline-flex items-center gap-1.5 shadow-sm">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#0071e3]"></span>
                    {badge}
                </span>
            )}
            <h1 className="text-[32px] sm:text-[48px] md:text-[56px] font-semibold text-[#1d1d1f] max-w-4xl mb-4 tracking-[-0.02em] font-sans leading-[1.08]">
                {title}
            </h1>
            {subtitle && (
                <p className="text-[16px] sm:text-[19px] text-[#1d1d1f]/70 max-w-2xl font-sans font-normal leading-[1.4]">
                    {subtitle}
                </p>
            )}
        </section>
    );
}

