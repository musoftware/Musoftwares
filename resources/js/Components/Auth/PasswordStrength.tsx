import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const RULES: RegExp[] = [/.{8,}/, /(?=.*[a-z])(?=.*[A-Z])/, /\d/, /[^a-zA-Z\d]/];

const LEVELS: Record<number, { label: string; bar: string; text: string }> = {
    1: { label: 'general.weak', bar: 'bg-[#E8710A]', text: 'text-[#B35500]' },
    2: { label: 'general.fair', bar: 'bg-[#E8710A]', text: 'text-[#B35500]' },
    3: { label: 'general.good', bar: 'bg-[#1E8E3E]', text: 'text-[#1E8E3E]' },
    4: { label: 'general.strong', bar: 'bg-[#1E8E3E]', text: 'text-[#1E8E3E]' },
};

export const passwordScore = (password: string): number => RULES.filter((rule) => rule.test(password)).length;

/** Four short bars under a new password. Shows nothing until the user types. */
export function PasswordStrength({ password }: { password: string }) {
    const score = passwordScore(password);
    const level = LEVELS[score];
    if (!password || !level) return null;

    return (
        <div className="calm-fade flex items-center gap-3" aria-live="polite">
            <div className="flex flex-1 gap-1">
                {[1, 2, 3, 4].map((step) => (
                    <span
                        key={step}
                        className={cn('h-1 flex-1 rounded-full transition-colors duration-200', score >= step ? level.bar : 'bg-black/[0.06] dark:bg-white/10')}
                    />
                ))}
            </div>
            <span className={cn('text-xs font-medium', level.text)}>{__(level.label)}</span>
        </div>
    );
}

/** Live "match / no match" line under the confirm field. */
export function PasswordMatch({ password, confirmation }: { password: string; confirmation: string }) {
    if (!confirmation) return null;
    const matches = password === confirmation;

    return (
        <p key={String(matches)} className={cn('calm-fade text-[13px]', matches ? 'text-[#1E8E3E]' : 'text-[#D93025]')}>
            {__(matches ? 'general.passwords_match' : 'general.passwords_do_not_match')}
        </p>
    );
}
