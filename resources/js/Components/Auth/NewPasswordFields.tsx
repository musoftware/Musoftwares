import { __ } from '@/lib/i18n';
import { PasswordField } from './AuthField';
import { PasswordMatch, PasswordStrength } from './PasswordStrength';

type Props = {
    password: string;
    confirmation: string;
    errors: Partial<Record<'password' | 'password_confirmation', string>>;
    onPassword: (value: string) => void;
    onConfirmation: (value: string) => void;
};

/** New password plus confirm, with the strength bars and a live match line. */
export function NewPasswordFields({ password, confirmation, errors, onPassword, onConfirmation }: Props) {
    return (
        <>
            <PasswordField
                id="password"
                label={__('general.new_password')}
                value={password}
                autoComplete="new-password"
                autoFocus
                error={errors.password}
                hint={<PasswordStrength password={password} />}
                onChange={(e) => onPassword(e.target.value)}
            />
            <PasswordField
                id="password_confirmation"
                label={__('general.confirm_password')}
                value={confirmation}
                autoComplete="new-password"
                canReveal={false}
                error={errors.password_confirmation}
                hint={<PasswordMatch password={password} confirmation={confirmation} />}
                onChange={(e) => onConfirmation(e.target.value)}
            />
        </>
    );
}
