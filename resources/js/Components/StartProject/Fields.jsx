import { __ } from '@/lib/i18n';

export const inputClass =
    'w-full min-h-[44px] rounded-xl border border-black/15 bg-white px-3.5 py-2.5 text-[15px] text-[#1d1d1f] focus:border-[#0071e3] focus:outline-none focus:ring-2 focus:ring-[#0071e3]/30 aria-[invalid=true]:border-[#d70015]';

/** Label, control and the error message right under it, linked for screen readers. */
export function Field({ id, label, error, children }) {
    return (
        <div className="space-y-1.5">
            <label htmlFor={id} className="block text-[14px] font-semibold text-[#1d1d1f]">{label}</label>
            {children({ id, 'aria-invalid': error ? 'true' : 'false', 'aria-describedby': error ? `${id}-error` : undefined })}
            {error && <p id={`${id}-error`} className="text-[13px] text-[#d70015]">{__(error)}</p>}
        </div>
    );
}

/** A group of radio or checkbox cards. The native input stays visible so keyboard and screen readers work as usual. */
export function ChoiceGroup({ legend, hint, error, name, type = 'radio', options, isChecked, onToggle, columns = 'sm:grid-cols-2' }) {
    const errorId = error ? `${name}-error` : undefined;

    return (
        <fieldset className="space-y-3" aria-describedby={errorId}>
            <legend className="text-[15px] font-semibold text-[#1d1d1f]">{legend}</legend>
            {hint && <p className="-mt-1 text-[13px] text-[#1d1d1f]/65">{hint}</p>}
            {error && <p id={errorId} className="text-[13px] text-[#d70015]">{__(error)}</p>}
            <div className={`grid grid-cols-1 gap-3 ${columns}`}>
                {options.map((option) => (
                    <ChoiceCard key={option.id} name={name} type={type} option={option} checked={isChecked(option.id)} onToggle={onToggle} />
                ))}
            </div>
        </fieldset>
    );
}

function ChoiceCard({ name, type, option, checked, onToggle }) {
    const Icon = option.icon;

    return (
        <label className={`flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-colors ${checked ? 'border-[#0071e3] bg-[#0071e3]/5' : 'border-black/10 bg-white hover:border-black/25'}`}>
            <input
                type={type}
                name={name}
                value={option.id}
                checked={checked}
                onChange={() => onToggle(option.id)}
                className="mt-1 h-4 w-4 shrink-0 accent-[#0071e3]"
            />
            {Icon && <Icon className={`mt-0.5 h-5 w-5 shrink-0 ${checked ? 'text-[#0071e3]' : 'text-[#1d1d1f]/55'}`} aria-hidden="true" />}
            <span className="min-w-0">
                <span className="block text-[15px] font-semibold text-[#1d1d1f]">{option.title}</span>
                {option.desc && <span className="mt-0.5 block text-[13px] leading-relaxed text-[#1d1d1f]/65">{option.descLtr ? <bdi dir="ltr">{option.desc}</bdi> : option.desc}</span>}
                {option.time && <span className="mt-0.5 block text-[13px] font-medium text-[#0071e3]">{option.time}</span>}
            </span>
        </label>
    );
}
