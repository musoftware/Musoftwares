import React from 'react';

export default function AuthIllustration() {
    return (
        <div className="w-full max-w-[360px] mx-auto select-none opacity-90 dark:opacity-85">
            <svg
                viewBox="0 0 360 260"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className="w-full h-auto"
            >
                {/* Calm, minimal floor line */}
                <line x1="30" y1="230" x2="330" y2="230" stroke="currentColor" strokeOpacity="0.15" strokeWidth="1.5" strokeLinecap="round" />

                {/* Minimalist Desk / Table */}
                <rect x="80" y="150" width="160" height="6" rx="3" fill="#64748b" fillOpacity="0.2" className="dark:fill-zinc-700" />
                <path d="M96 156 L90 230" stroke="#64748b" strokeOpacity="0.4" strokeWidth="3" strokeLinecap="round" className="dark:stroke-zinc-600" />
                <path d="M224 156 L230 230" stroke="#64748b" strokeOpacity="0.4" strokeWidth="3" strokeLinecap="round" className="dark:stroke-zinc-600" />

                {/* Desktop Monitor / Screen */}
                <rect x="120" y="90" width="80" height="52" rx="6" fill="currentColor" fillOpacity="0.08" stroke="currentColor" strokeOpacity="0.3" strokeWidth="1.5" />
                {/* Monitor stand */}
                <path d="M150 142 L150 150 M170 142 L170 150 M144 150 L176 150" stroke="currentColor" strokeOpacity="0.3" strokeWidth="2" strokeLinecap="round" />
                
                {/* Calm UI elements inside monitor */}
                <rect x="130" y="100" width="40" height="4" rx="2" fill="currentColor" fillOpacity="0.2" />
                <rect x="130" y="108" width="60" height="2" rx="1" fill="currentColor" fillOpacity="0.15" />
                <rect x="130" y="114" width="50" height="2" rx="1" fill="currentColor" fillOpacity="0.15" />
                <rect x="130" y="122" width="24" height="12" rx="2" fill="#6366f1" fillOpacity="0.8" />
                <rect x="158" y="122" width="32" height="12" rx="2" fill="currentColor" fillOpacity="0.1" />

                {/* Minimal Plant on desk */}
                <path d="M210 150 V138 M210 144 Q216 140 214 134 Q208 138 210 144 M210 146 Q204 142 206 136 Q212 140 210 146" fill="#10b981" fillOpacity="0.7" stroke="#10b981" strokeWidth="1.5" strokeLinecap="round" />
                <rect x="204" y="138" width="12" height="12" rx="2" fill="#64748b" fillOpacity="0.3" className="dark:fill-zinc-600" />

                {/* Calm Person Sitting at Chair */}
                {/* Minimal Chair */}
                <path d="M66 160 L66 230 M60 180 L72 180 M66 180 L66 150 L56 150" stroke="currentColor" strokeOpacity="0.3" strokeWidth="3" strokeLinecap="round" />
                
                {/* Minimal Person Figure */}
                {/* Legs */}
                <path d="M66 176 L86 176 L86 226" stroke="currentColor" strokeOpacity="0.8" strokeWidth="8" strokeLinecap="round" strokeLinejoin="round" />
                
                {/* Torso */}
                <path d="M64 130 C64 125 70 120 78 120 L82 120 C88 120 92 126 92 134 L92 174 L64 174 Z" fill="#6366f1" fillOpacity="0.9" />
                
                {/* Head */}
                <circle cx="78" cy="104" r="10" fill="currentColor" fillOpacity="0.7" />
                
                {/* Arm resting on desk */}
                <path d="M84 132 L104 146 L118 146" stroke="currentColor" strokeOpacity="0.7" strokeWidth="6" strokeLinecap="round" strokeLinejoin="round" />

                {/* Minimal Coffee Mug / Cup */}
                <rect x="104" y="142" width="8" height="8" rx="2" fill="currentColor" fillOpacity="0.4" />
            </svg>
        </div>
    );
}
