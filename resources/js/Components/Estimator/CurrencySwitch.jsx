const OPTIONS = [
    { label: 'USD', usd: true },
    { label: 'EGP', usd: false },
];

/** USD / EGP switch used by both estimator modes. */
export default function CurrencySwitch({ isUsd, onChange }) {
    return (
        <div className="flex items-center rounded-full bg-[#f5f5f7] p-1 border border-black/5">
            {OPTIONS.map((option) => {
                const active = isUsd === option.usd;
                return (
                    <button
                        key={option.label}
                        type="button"
                        aria-pressed={active}
                        onClick={() => onChange(option.usd)}
                        className={`px-3 py-0.5 text-xs font-semibold rounded-full transition-all cursor-pointer ${
                            active ? 'bg-white text-[#1d1d1f] shadow-sm' : 'text-[#1d1d1f]/60 hover:text-[#1d1d1f]'
                        }`}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
