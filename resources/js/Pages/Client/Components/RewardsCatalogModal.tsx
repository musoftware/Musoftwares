import React, { useEffect, useState } from 'react';
import { X, Award, Check, AlertCircle, Sparkles, Shield, ArrowRight } from 'lucide-react';
import axios from 'axios';

interface RewardItem {
    id: number;
    name: string;
    description: string;
    reward_type: string;
    points_cost: number;
    discount_value: number;
    discount_type: string;
}

interface RewardsCatalogModalProps {
    isOpen: boolean;
    onClose: () => void;
    userPoints: number;
    onRewardRedeemed: (newBalance: number) => void;
}

export const RewardsCatalogModal: React.FC<RewardsCatalogModalProps> = ({
    isOpen,
    onClose,
    userPoints,
    onRewardRedeemed,
}) => {
    const [rewards, setRewards] = useState<RewardItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [redeemingId, setRedeemingId] = useState<number | null>(null);
    const [statusMsg, setStatusMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    useEffect(() => {
        if (isOpen) {
            fetchRewards();
            setStatusMsg(null);
        }
    }, [isOpen]);

    const fetchRewards = async () => {
        try {
            setLoading(true);
            const res = await axios.get('/api/portal/loyalty/rewards');
            setRewards(res.data?.data || []);
        } catch (err) {
            setRewards([
                {
                    id: 1,
                    name: '15% Deducted on Next Invoice',
                    description: 'Applied automatically to your next engineering milestone or recurring sprint settlement.',
                    reward_type: 'invoice_discount',
                    points_cost: 300,
                    discount_value: 15,
                    discount_type: 'percentage',
                },
                {
                    id: 2,
                    name: '5 Hours Priority Incident Response',
                    description: 'Dedicated core engineer availability for zero-hour patches, audits, or load balancing.',
                    reward_type: 'free_maintenance_hours',
                    points_cost: 600,
                    discount_value: 5,
                    discount_type: 'fixed',
                },
                {
                    id: 3,
                    name: '30-Day Desktop Runtime License Lease',
                    description: 'Complimentary activation lease for background automation and headless scraper workers.',
                    reward_type: 'license_extension',
                    points_cost: 1000,
                    discount_value: 30,
                    discount_type: 'fixed',
                },
            ]);
        } finally {
            setLoading(false);
        }
    };

    const handleRedeem = async (reward: RewardItem) => {
        if (userPoints < reward.points_cost) {
            setStatusMsg({ type: 'error', text: 'Insufficient loyalty capital for this privilege.' });
            return;
        }

        try {
            setRedeemingId(reward.id);
            setStatusMsg(null);
            const res = await axios.post(`/api/portal/loyalty/rewards/${reward.id}/redeem`);
            const newBal = res.data?.data?.new_points_balance ?? (userPoints - reward.points_cost);
            setStatusMsg({ type: 'success', text: `Privilege unlocked: ${reward.name}` });
            onRewardRedeemed(newBal);
        } catch (err: any) {
            setStatusMsg({
                type: 'error',
                text: err.response?.data?.message || 'Transaction could not be completed. Please try again.',
            });
        } finally {
            setRedeemingId(null);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-2xl animate-in fade-in duration-200">
            <div className="relative w-full max-w-xl bg-[#141416] border border-white/10 rounded-3xl shadow-2xl p-6 sm:p-7 overflow-hidden">
                {/* Modal Header */}
                <div className="flex items-center justify-between pb-5 border-b border-white/5">
                    <div className="flex items-center gap-2.5">
                        <div className="p-2 rounded-xl bg-white text-black shadow-sm">
                            <Award className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-white tracking-tight">
                                Client Loyalty Privileges
                            </h3>
                            <p className="text-xs text-zinc-400 mt-0.5">
                                Convert self-service capital into direct financial deductions and engineering time.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-full text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Balance Status Banner */}
                <div className="mt-4 p-3.5 rounded-2xl bg-zinc-900/90 border border-white/5 flex items-center justify-between text-xs font-mono">
                    <span className="text-zinc-400">
                        AVAILABLE ASSETS: <strong className="text-white text-sm font-semibold tabular-nums ml-1">{userPoints.toLocaleString()} PTS</strong>
                    </span>
                    <span className="text-[11px] text-emerald-400 font-medium">
                        Instant Zero-Loss Redemption
                    </span>
                </div>

                {statusMsg && (
                    <div className={`mt-3 p-3.5 rounded-xl text-xs flex items-center gap-2.5 ${statusMsg.type === 'success' ? 'bg-emerald-950/40 border border-emerald-500/20 text-emerald-300' : 'bg-red-950/40 border border-red-500/20 text-red-300'}`}>
                        {statusMsg.type === 'success' ? <Check className="w-4 h-4 flex-shrink-0 text-emerald-400" /> : <AlertCircle className="w-4 h-4 flex-shrink-0 text-red-400" />}
                        <span>{statusMsg.text}</span>
                    </div>
                )}

                {/* Privilege Cards */}
                <div className="mt-4 space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    {loading ? (
                        <div className="py-12 text-center text-xs text-zinc-500 font-mono">Retrieving privilege catalog...</div>
                    ) : (
                        rewards.map((reward) => {
                            const canAfford = userPoints >= reward.points_cost;

                            return (
                                <div
                                    key={reward.id}
                                    className="p-4 rounded-2xl bg-zinc-950/60 border border-white/5 hover:border-white/10 transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                                >
                                    <div className="space-y-1">
                                        <div className="text-sm font-semibold text-white flex items-center gap-2">
                                            <span>{reward.name}</span>
                                            <span className="font-mono text-[11px] px-2.5 py-0.5 rounded-full bg-zinc-900 border border-white/10 text-zinc-300 tabular-nums">
                                                {reward.points_cost} PTS
                                            </span>
                                        </div>
                                        <p className="text-xs text-zinc-400 max-w-sm leading-relaxed">
                                            {reward.description}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        disabled={!canAfford || redeemingId === reward.id}
                                        onClick={() => handleRedeem(reward)}
                                        className={`px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all active:scale-95 ${
                                            canAfford
                                                ? 'bg-white text-black hover:bg-zinc-200 shadow-sm cursor-pointer'
                                                : 'bg-zinc-900 text-zinc-500 cursor-not-allowed border border-white/5'
                                        }`}
                                    >
                                        {redeemingId === reward.id
                                            ? 'Settling...'
                                            : canAfford
                                            ? 'Unlock Privilege'
                                            : 'Requires Capital'}
                                    </button>
                                </div>
                            );
                        })
                    )}
                </div>

                <div className="mt-6 pt-4 border-t border-white/5 flex justify-end">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded-xl text-xs font-semibold bg-zinc-900 text-zinc-300 hover:text-white border border-white/5 transition-colors"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>
    );
};
