import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('general.confirm_password')} />

            <div className="space-y-6">
                <div className="space-y-1.5">
                    <h1 className="text-2xl font-bold tracking-tight text-[#1d1d1f] font-sans">{__('general.confirm_your_password')}</h1>
                    <p className="text-xs text-[#1d1d1f]/60 font-sans">{__('general.please_confirm_your_identity_before_accessing_restricted_settings')}</p>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="password" className="text-xs font-semibold text-[#1d1d1f]/80">
                            {__('general.password')}
                        </Label>
                        <div className="relative">
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                placeholder="••••••••"
                                value={data.password}
                                autoComplete="current-password"
                                autoFocus
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                className="h-10 px-3 py-2 pe-10 text-sm rounded-xl border-black/10 bg-[#f5f5f7]/50 focus:bg-white focus-visible:ring-2 focus-visible:ring-[#0071e3] transition-all font-normal"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute end-3 top-3 text-[#1d1d1f]/40 hover:text-[#1d1d1f] transition-colors"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                        {errors.password && (
                            <p className="text-xs text-[#ff3b30] font-medium mt-1">{errors.password}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <Button
                            className="w-full h-11 bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-sm rounded-[980px] shadow-md shadow-blue-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer"
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin text-white" />
                                    <span>{__('general.verifying_identity')}</span>
                                </>
                            ) : (
                                <span>{__('general.confirm_identity')}</span>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
