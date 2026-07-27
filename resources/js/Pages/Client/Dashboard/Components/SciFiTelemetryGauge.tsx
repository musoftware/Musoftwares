import React from 'react';

interface SciFiTelemetryGaugeProps {
    percentage: number;
    title: string;
    subtitle?: string;
    color?: string; // CSS color string or class
}

export default function SciFiTelemetryGauge({
    percentage,
    title,
    subtitle,
    color = 'var(--scifi-primary)',
}: SciFiTelemetryGaugeProps) {
    const clampedPct = Math.min(Math.max(percentage, 0), 100);
    const radius = 38;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = circumference - (clampedPct / 100) * circumference;

    return (
        <div className="scifi-panel group relative flex flex-col items-center justify-center p-4 rounded-xl transition-all duration-300">
            <div className="scifi-corner-tl" />
            <div className="scifi-corner-tr" />
            <div className="scifi-corner-bl" />
            <div className="scifi-corner-br" />

            {/* Top HUD Ticks */}
            <div className="absolute top-2 left-3 text-[9px] font-mono tracking-widest text-[var(--scifi-text-muted)] opacity-70">
                [ HUD_GAUGE ]
            </div>
            <div className="absolute top-2 right-3 text-[9px] font-mono tracking-widest text-[var(--scifi-primary-light)]">
                SYS::RADAR
            </div>

            {/* Circular Gauge Frame directly modeled after the image design */}
            <div className="relative my-3 flex items-center justify-center">
                <svg className="h-28 w-28 -rotate-90 transform" viewBox="0 0 100 100">
                    {/* Outer HUD Decorative Dashed Circle */}
                    <circle
                        cx="50"
                        cy="50"
                        r="46"
                        fill="none"
                        stroke="var(--scifi-panel-border)"
                        strokeWidth="1.5"
                        strokeDasharray="3 3"
                    />
                    {/* Background Progress Ring */}
                    <circle
                        cx="50"
                        cy="50"
                        r={radius}
                        fill="none"
                        stroke="rgba(255, 255, 255, 0.08)"
                        strokeWidth="6"
                    />
                    {/* Foreground Animated Progress Arc */}
                    <circle
                        cx="50"
                        cy="50"
                        r={radius}
                        fill="none"
                        stroke={color}
                        strokeWidth="6"
                        strokeDasharray={circumference}
                        strokeDashoffset={strokeDashoffset}
                        strokeLinecap="round"
                        className="transition-all duration-700 ease-out"
                        style={{
                            filter: `drop-shadow(0 0 6px ${color})`,
                        }}
                    />
                    {/* Inner HUD Tech Tick Rings */}
                    <circle
                        cx="50"
                        cy="50"
                        r="30"
                        fill="none"
                        stroke="var(--scifi-panel-border)"
                        strokeWidth="1"
                        strokeDasharray="6 6"
                        className="opacity-40"
                    />
                </svg>

                {/* Center Percentage Display */}
                <div className="absolute flex flex-col items-center justify-center">
                    <span 
                        className="font-mono text-2xl font-extrabold tracking-wider drop-shadow-md"
                        style={{ color: 'var(--scifi-primary-light)' }}
                    >
                        {clampedPct}%
                    </span>
                    <span className="text-[9px] font-mono uppercase tracking-widest text-slate-400">
                        VAL
                    </span>
                </div>
            </div>

            {/* Title & Subtitle */}
            <div className="mt-1 text-center">
                <h4 className="font-mono text-xs font-bold uppercase tracking-wider text-slate-200 group-hover:text-[var(--scifi-primary-light)] transition-colors">
                    {title}
                </h4>
                {subtitle && (
                    <p className="mt-0.5 text-[10px] font-mono text-slate-400 truncate max-w-[130px]">
                        {subtitle}
                    </p>
                )}
            </div>

            {/* Striped Telemetry Bar under title */}
            <div className="mt-3 flex w-full gap-1 px-2">
                {Array.from({ length: 10 }).map((_, i) => (
                    <div
                        key={i}
                        className="h-1 flex-1 rounded-xs transition-all duration-300"
                        style={{
                            backgroundColor: i < Math.round((clampedPct / 100) * 10)
                                ? color
                                : 'rgba(255, 255, 255, 0.1)',
                            boxShadow: i < Math.round((clampedPct / 100) * 10)
                                ? `0 0 4px ${color}`
                                : 'none',
                        }}
                    />
                ))}
            </div>
        </div>
    );
}
