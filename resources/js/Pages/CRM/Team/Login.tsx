import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { Loader2, Users, Lock, Mail, Eye, EyeOff } from 'lucide-react';

export default function Login() {
    const [showPassword, setShowPassword] = React.useState(false);

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('crm.team.login.store'));
    };

    return (
        <>
            <Head title={__('crm.team_login_title')} />

            <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 flex items-center justify-center p-4">
                {/* Ambient glow effects */}
                <div className="absolute inset-0 overflow-hidden pointer-events-none">
                    <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl" />
                    <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-violet-500/8 rounded-full blur-3xl" />
                </div>

                <div className="relative w-full max-w-md">
                    {/* Logo / Header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-xl shadow-indigo-500/20 mb-4">
                            <Users className="w-7 h-7 text-white" />
                        </div>
                        <h1 className="text-2xl font-bold text-white tracking-tight">
                            {__('crm.team_login_title')}
                        </h1>
                        <p className="text-sm text-slate-400 mt-1.5">
                            {__('crm.team_login_subtitle')}
                        </p>
                    </div>

                    {/* Login Card */}
                    <div className="bg-white/[0.03] backdrop-blur-xl border border-white/[0.06] rounded-2xl p-8 shadow-2xl">
                        <form onSubmit={handleSubmit} className="space-y-5">
                            {/* Email */}
                            <div className="space-y-1.5">
                                <label className="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                    {__('crm.email_address')}
                                </label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                                    <Input
                                        type="email"
                                        required
                                        placeholder={__('general.name_company_com')}
                                        value={form.data.email}
                                        onChange={e => form.setData('email', e.target.value)}
                                        className="pl-10 bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-500 focus:border-indigo-500/50 focus:ring-indigo-500/20 h-11"
                                    />
                                </div>
                                {form.errors.email && (
                                    <p className="text-xs text-rose-400 mt-1">{form.errors.email}</p>
                                )}
                            </div>

                            {/* Password */}
                            <div className="space-y-1.5">
                                <label className="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                    {__('crm.temporary_password')}
                                </label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                                    <Input
                                        type={showPassword ? 'text' : 'password'}
                                        required
                                        placeholder="••••••••"
                                        value={form.data.password}
                                        onChange={e => form.setData('password', e.target.value)}
                                        className="pl-10 pr-10 bg-white/[0.04] border-white/[0.08] text-white placeholder:text-slate-500 focus:border-indigo-500/50 focus:ring-indigo-500/20 h-11"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition"
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                {form.errors.password && (
                                    <p className="text-xs text-rose-400 mt-1">{form.errors.password}</p>
                                )}
                            </div>

                            {/* Remember me */}
                            <label className="flex items-center gap-2 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    checked={form.data.remember}
                                    onChange={e => form.setData('remember', e.target.checked)}
                                    className="rounded border-white/10 bg-white/[0.04] text-indigo-500 focus:ring-indigo-500/30 h-4 w-4"
                                />
                                <span className="text-sm text-slate-400 group-hover:text-slate-300 transition">
                                    {__('crm.remember_me')}
                                </span>
                            </label>

                            {/* Submit */}
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="w-full h-11 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold shadow-lg shadow-indigo-500/25 transition-all duration-200"
                            >
                                {form.processing ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    __('crm.team_login_button')
                                )}
                            </Button>
                        </form>
                    </div>

                    {/* Footer */}
                    <p className="text-center text-xs text-slate-600 mt-6">
                        {__('crm.team_login_footer')}
                    </p>
                </div>
            </div>
        </>
    );
}
