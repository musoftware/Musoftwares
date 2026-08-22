import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    Info, Ticket, Wallet, Calendar, UserCheck, 
    Users, ArrowRight, History, ArrowLeft, Sparkles, Check 
} from 'lucide-react';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';

interface Voucher {
    id: number;
    name: string;
    description: string;
    type: 'fixed' | 'percentage';
    reward_percentage: number | null;
    spend_amount_user_currency: number;
    reward_amount_user_currency: number;
    expires_at: string | null;
    max_uses_per_user: number | null;
    current_uses: number;
    max_total_uses: number | null;
}

interface Redemption {
    id: number;
    spent_amount: number;
    reward_amount: number;
    created_at: string;
    voucher: {
        name: string;
    };
}

interface Props {
    auth: {
        user: any;
    };
    vouchers: Voucher[];
    redemptions: {
        data: Redemption[];
        links: any[];
    };
}

export default function Index({ auth, vouchers, redemptions }: Props) {
    const userCurrency = auth.user?.currency || 'EGP';

    return (
        <AuthenticatedLayout>
            <Head title={`${__('vouchers.title')} — Musoftwares Studio`} />

            <div className="w-full bg-[#f5f5f7] text-[#1d1d1f] min-h-[calc(100vh-68px)] font-sans antialiased selection:bg-[#0071e3]/20 selection:text-[#0071e3]">
                
                {/* Hero Header */}
                <div className="w-full bg-white border-b border-black/5 py-8 px-6 sm:px-10">
                    <div className="max-w-[1400px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="space-y-1.5">
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center text-xs font-semibold text-[#0071e3] hover:text-[#0077ed] transition-colors mb-1"
                            >
                                <ArrowLeft className="me-1.5 h-3.5 w-3.5" />
                                {__('general.back_to_dashboard')}
                            </Link>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] font-sans">
                                {__('vouchers.title')}
                            </h1>
                            <p className="text-xs sm:text-sm text-[#1d1d1f]/60 font-sans">
                                {__('vouchers.subtitle')}
                            </p>
                        </div>

                        <Link
                            href={route('financial.add-balance')}
                            className="px-5 py-2.5 bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold rounded-[980px] transition-all flex items-center gap-2 shadow-sm shadow-blue-500/20 cursor-pointer shrink-0"
                        >
                            <Wallet className="w-4 h-4" />
                            <span>{__('vouchers.add_balance')}</span>
                        </Link>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-[1400px] mx-auto px-6 sm:px-10 py-8 space-y-8">
                    
                    {/* How it works alert */}
                    {vouchers.length > 0 && (
                        <div className="flex items-start gap-3 p-5 rounded-[20px] bg-white border border-black/5 shadow-sm text-xs sm:text-sm">
                            <div className="w-9 h-9 rounded-xl bg-[#0071e3]/10 text-[#0071e3] flex items-center justify-center shrink-0">
                                <Info className="w-4 h-4" />
                            </div>
                            <div>
                                <strong className="font-bold text-[#1d1d1f] block mb-0.5">
                                    {__('vouchers.how_vouchers_work.title')}
                                </strong>
                                <span className="text-[#1d1d1f]/70">
                                    {__('vouchers.how_vouchers_work.description')}
                                </span>
                            </div>
                        </div>
                    )}

                    {/* Vouchers Bento Grid */}
                    {vouchers.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {vouchers.map((voucher) => (
                                <div
                                    key={voucher.id}
                                    className="bg-white border border-black/5 rounded-[24px] shadow-sm flex flex-col justify-between overflow-hidden hover:border-[#0071e3]/30 hover:shadow-md transition-all group"
                                >
                                    <div className="p-6 sm:p-7 space-y-5">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-[#0071e3] block mb-1">
                                                    Studio Promo Reward
                                                </span>
                                                <h3 className="text-base font-bold text-[#1d1d1f] font-sans">
                                                    {voucher.name}
                                                </h3>
                                            </div>
                                            <span className="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200/60 shrink-0">
                                                {voucher.type === 'percentage' ? `${voucher.reward_percentage}%` : __('vouchers.fixed')}
                                            </span>
                                        </div>

                                        {voucher.description && (
                                            <p className="text-xs text-[#1d1d1f]/60 font-sans leading-relaxed">
                                                {voucher.description}
                                            </p>
                                        )}

                                        {/* Spend & Get Bento Box */}
                                        <div className="bg-[#f5f5f7] rounded-[18px] p-4 flex items-center justify-between border border-black/5">
                                            <div>
                                                <span className="text-[10px] font-mono uppercase font-bold text-[#1d1d1f]/50 block mb-0.5">
                                                    {__('vouchers.spend')}
                                                </span>
                                                <strong className="text-sm font-bold font-mono text-[#1d1d1f]">
                                                    {formatCurrency(voucher.spend_amount_user_currency, userCurrency)}
                                                </strong>
                                            </div>
                                            <ArrowRight className="h-4 w-4 text-[#0071e3]" />
                                            <div className="text-end">
                                                <span className="text-[10px] font-mono uppercase font-bold text-[#1d1d1f]/50 block mb-0.5">
                                                    {__('vouchers.get')}
                                                </span>
                                                <strong className="text-sm font-bold font-mono text-emerald-600">
                                                    {formatCurrency(voucher.reward_amount_user_currency, userCurrency)}
                                                </strong>
                                            </div>
                                        </div>

                                        <div className="space-y-2 text-xs text-[#1d1d1f]/60">
                                            <div className="flex items-center gap-1.5">
                                                <Calendar className="h-3.5 w-3.5 text-[#0071e3]" />
                                                <span>{__('vouchers.valid_until')}:</span>
                                                <span className="font-semibold text-[#1d1d1f]">
                                                    {voucher.expires_at || __('vouchers.no_expiry')}
                                                </span>
                                            </div>

                                            {voucher.max_uses_per_user && (
                                                <div className="flex items-center gap-1.5">
                                                    <UserCheck className="h-3.5 w-3.5 text-amber-600" />
                                                    <span>{__('vouchers.max')} {voucher.max_uses_per_user} {__('vouchers.per_user')}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="p-4 bg-[#f5f5f7]/60 border-t border-black/5 flex items-center justify-between">
                                        <div className="flex items-center gap-1.5 text-[11px] text-[#1d1d1f]/50 font-mono">
                                            <Users className="h-3.5 w-3.5" />
                                            <span>{voucher.current_uses} {__('vouchers.uses')}</span>
                                        </div>

                                        <Link
                                            href={route('financial.add-balance')}
                                            className="px-4 py-1.5 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-semibold shadow-xs transition-all"
                                        >
                                            {__('vouchers.add_balance_and_pay')}
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white border border-black/5 rounded-[24px] p-12 text-center shadow-sm max-w-xl mx-auto">
                            <div className="w-14 h-14 rounded-2xl bg-[#0071e3]/10 flex items-center justify-center text-[#0071e3] mx-auto mb-4">
                                <Ticket className="w-7 h-7" />
                            </div>
                            <h3 className="text-base font-bold text-[#1d1d1f] font-sans">{__('vouchers.empty.title')}</h3>
                            <p className="text-xs text-[#1d1d1f]/60 max-w-md mx-auto mt-1 leading-relaxed">
                                {__('vouchers.empty.description')}
                            </p>
                        </div>
                    )}

                    {/* Redemption History Table */}
                    {redemptions?.data?.length > 0 && (
                        <div className="bg-white rounded-[24px] border border-black/5 shadow-sm p-6 sm:p-8 space-y-6">
                            <div className="flex items-center gap-2 border-b border-black/5 pb-4">
                                <History className="h-5 w-5 text-[#0071e3]" />
                                <h3 className="font-bold text-base text-[#1d1d1f] font-sans">
                                    {__('vouchers.redemptions.title')}
                                </h3>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-start border-collapse">
                                    <thead>
                                        <tr className="bg-[#f5f5f7]/60 text-[11px] font-semibold text-[#1d1d1f]/50 uppercase tracking-wider border-b border-black/5">
                                            <th className="py-3 ps-6 text-start">{__('vouchers.voucher')}</th>
                                            <th className="py-3 px-4 text-start">{__('vouchers.redemptions.spent')}</th>
                                            <th className="py-3 px-4 text-start">{__('vouchers.redemptions.reward_received')}</th>
                                            <th className="py-3 pe-6 text-end">{__('vouchers.redemptions.date')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-black/5 text-xs sm:text-sm">
                                        {redemptions.data.map((redemption) => (
                                            <tr key={redemption.id} className="hover:bg-[#f5f5f7]/40 transition-colors">
                                                <td className="py-4 ps-6 font-semibold text-[#1d1d1f]">
                                                    {redemption.voucher.name}
                                                </td>
                                                <td className="py-4 px-4 font-mono font-medium text-[#1d1d1f]/70">
                                                    {formatCurrency(redemption.spent_amount, userCurrency)}
                                                </td>
                                                <td className="py-4 px-4 font-mono font-bold text-emerald-600">
                                                    +{formatCurrency(redemption.reward_amount, userCurrency)}
                                                </td>
                                                <td className="py-4 pe-6 text-end text-xs text-[#1d1d1f]/50 font-sans">
                                                    {new Date(redemption.created_at).toLocaleDateString()} {new Date(redemption.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
