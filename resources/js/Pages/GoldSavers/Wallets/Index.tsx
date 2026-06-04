import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import GoldSaversTabs from '../Components/GoldSaversTabs';
import { formatNumber } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { Wallet, Target, Search, ArrowUpDown, Plus, Scale, LayoutGrid } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

interface GoldWallet {
    id: number;
    name: string;
    goal_type: string;
    target_grams: number;
    target_amount: number;
    balance_grams: number;
    balance_amount: number;
    currency: string;
    transactions: any[];
}

interface WalletsProps {
    wallets: GoldWallet[];
    hasMultiWallets: boolean;
    hasGoalTracking: boolean;
}

export default function WalletsIndex({ wallets, hasMultiWallets, hasGoalTracking }: WalletsProps) {
    const [isCreating, setIsCreating] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [goalFilter, setGoalFilter] = useState('All');
    const [sortBy, setSortBy] = useState('recent');
    const [newWallet, setNewWallet] = useState({
        name: '',
        goal_type: 'Investment',
        target_grams: '',
    });

    const handleCreateWallet = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('isaas.gold-savers.wallets.store'), newWallet, {
            onSuccess: () => {
                setIsCreating(false);
                setNewWallet({ name: '', goal_type: 'Investment', target_grams: '' });
            }
        });
    };

    const filteredWallets = useMemo(() => {
        return wallets.filter(wallet => {
            const matchesSearch = wallet.name.toLowerCase().includes(searchQuery.toLowerCase());
            const matchesGoal = goalFilter === 'All' || wallet.goal_type === goalFilter;
            return matchesSearch && matchesGoal;
        }).sort((a, b) => {
            if (sortBy === 'balance_desc') return b.balance_grams - a.balance_grams;
            if (sortBy === 'balance_asc') return a.balance_grams - b.balance_grams;
            if (sortBy === 'target_progress') {
                const progA = a.target_grams > 0 ? a.balance_grams / a.target_grams : 0;
                const progB = b.target_grams > 0 ? b.balance_grams / b.target_grams : 0;
                return progB - progA;
            }
            return b.id - a.id;
        });
    }, [wallets, searchQuery, goalFilter, sortBy]);

    const totalGrams = useMemo(() => wallets.reduce((acc, w) => acc + Number(w.balance_grams || 0), 0), [wallets]);
    const totalTarget = useMemo(() => wallets.reduce((acc, w) => acc + Number(w.target_grams || 0), 0), [wallets]);
    const overallProgress = totalTarget > 0 ? (totalGrams / totalTarget) * 100 : 0;

    const goalTypes = useMemo(() => {
        const types = new Set(wallets.map(w => w.goal_type));
        return ['All', ...Array.from(types)];
    }, [wallets]);

    const containerVariants = {
        hidden: { opacity: 0 },
        show: {
            opacity: 1,
            transition: { staggerChildren: 0.1 }
        }
    };

    const itemVariants: any = {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0, transition: { type: "spring", stiffness: 300, damping: 24 } },
        exit: { opacity: 0, scale: 0.9, transition: { duration: 0.2 } }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">{__('erp.my_gold_wallets')}</h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('erp.gold_wallets')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header Stats */}
                    {wallets.length > 0 && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">{__('general.total_gold_saved')}</CardTitle>
                                    <Scale className="w-4 h-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{totalGrams.toFixed(2)} {__('general.g')}</div>
                                </CardContent>
                            </Card>
                            
                            {hasGoalTracking && (
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">{__('general.overall_goals_progress')}</CardTitle>
                                        <Target className="w-4 h-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{overallProgress.toFixed(1)}%</div>
                                        <div className="w-full bg-slate-100 rounded-full h-2 mt-4">
                                            <div 
                                                style={{ width: `${Math.min(overallProgress, 100)}%` }}
                                                className="bg-indigo-600 h-2 rounded-full" 
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            <Card className="flex flex-col items-center justify-center p-6 border-dashed">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-4">{__('erp.active_wallets')}: <span className="font-bold text-indigo-600">{wallets.length}</span></p>
                                    {(!wallets.length || hasMultiWallets) && (
                                        <Button onClick={() => setIsCreating(!isCreating)} variant="outline" className="gap-2">
                                            <Plus className="w-4 h-4" /> {isCreating ? __('general.cancel') : __('erp.create_new_wallet')}
                                        </Button>
                                    )}
                                </div>
                            </Card>
                        </div>
                    )}

                    {/* Filters Bar */}
                    {wallets.length > 0 && (
                        <div className="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                            <div className="relative w-full md:w-96">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                                <Input 
                                    className="pl-9 bg-slate-50 border-slate-200 focus-visible:ring-indigo-500" 
                                    placeholder={__('general.search_wallets')}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                            <div className="flex gap-3 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                                <div className="flex items-center gap-2">
                                    <LayoutGrid className="w-4 h-4 text-slate-400" />
                                    <Select value={goalFilter} onValueChange={(value) => setGoalFilter(value as string)}>
                                        <SelectTrigger className="w-[180px] bg-slate-50 border-slate-200">
                                            <SelectValue placeholder={__('general.filter_by_goal')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {goalTypes.map(type => (
                                                <SelectItem key={type} value={type}>{__(type)}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-center gap-2">
                                    <ArrowUpDown className="w-4 h-4 text-slate-400" />
                                    <Select value={sortBy} onValueChange={(value) => setSortBy(value as string)}>
                                        <SelectTrigger className="w-[180px] bg-slate-50 border-slate-200">
                                            <SelectValue placeholder={__('general.sort_by')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="recent">{__('general.recent')}</SelectItem>
                                            <SelectItem value="balance_desc">{__('general.highest_balance')}</SelectItem>
                                            <SelectItem value="balance_asc">{__('general.lowest_balance')}</SelectItem>
                                            {hasGoalTracking && <SelectItem value="target_progress">{__('general.closest_to_goal')}</SelectItem>}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Creation Form */}
                    <AnimatePresence>
                        {isCreating && (
                            <motion.div
                                initial={{ opacity: 0, height: 0, marginBottom: 0 }}
                                animate={{ opacity: 1, height: 'auto', marginBottom: 24 }}
                                exit={{ opacity: 0, height: 0, marginBottom: 0 }}
                                className="overflow-hidden"
                            >
                                <Card className="border-indigo-200 shadow-md bg-indigo-50/50">
                                    <CardHeader>
                                        <CardTitle className="text-lg text-indigo-900">{__('erp.create_new_wallet')}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <form onSubmit={handleCreateWallet} className="flex flex-col md:flex-row gap-4 items-end">
                                            <div className="space-y-2 flex-1 w-full">
                                                <label className="text-sm font-medium text-slate-700">{__('erp.wallet_name')}</label>
                                                <Input 
                                                    value={newWallet.name} 
                                                    onChange={e => setNewWallet({...newWallet, name: e.target.value})} 
                                                    placeholder={__('general.e_g_kids_college_fund')}
                                                    className="bg-white"
                                                    required 
                                                />
                                            </div>
                                            <div className="space-y-2 flex-1 w-full">
                                                <label className="text-sm font-medium text-slate-700">{__('general.goal_type')}</label>
                                                <Select 
                                                    value={newWallet.goal_type} 
                                                    onValueChange={value => setNewWallet({...newWallet, goal_type: value as string})}
                                                >
                                                    <SelectTrigger className="w-full bg-white">
                                                        <SelectValue placeholder={__('general.select_goal_type')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="Investment">{__('general.investment')}</SelectItem>
                                                        <SelectItem value="Wedding">{__('general.wedding')}</SelectItem>
                                                        <SelectItem value="Emergency">{__('general.emergency')}</SelectItem>
                                                        <SelectItem value="Kids">{__('general.kids_savings')}</SelectItem>
                                                        <SelectItem value="Hajj">{__('general.hajj_umrah')}</SelectItem>
                                                        <SelectItem value="Retirement">{__('general.retirement')}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            {hasGoalTracking && (
                                                <div className="space-y-2 flex-1 w-full">
                                                    <label className="text-sm font-medium text-slate-700">{__('gold_saver.target_grams')} ({__('general.optional')})</label>
                                                    <Input 
                                                        type="number"
                                                        step="0.01"
                                                        value={newWallet.target_grams} 
                                                        onChange={e => setNewWallet({...newWallet, target_grams: e.target.value})} 
                                                        placeholder={__('general.e_g_50_00')}
                                                        className="bg-white"
                                                    />
                                                </div>
                                            )}
                                            <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white w-full md:w-auto">{__('general.save_wallet')}</Button>
                                        </form>
                                    </CardContent>
                                </Card>
                            </motion.div>
                        )}
                    </AnimatePresence>

                    {/* Empty State if no wallets */}
                    {!wallets.length && !isCreating && (
                        <motion.div 
                            initial={{ opacity: 0 }} 
                            animate={{ opacity: 1 }} 
                            className="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-dashed border-slate-200"
                        >
                            <div className="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mb-6">
                                <Wallet className="w-10 h-10 text-indigo-400" />
                            </div>
                            <h3 className="text-2xl font-bold text-slate-800 mb-2">{__('erp.no_wallets_yet')}</h3>
                            <p className="text-muted-foreground max-w-md mb-8">
                                {__('general.start_your_gold_saving_journey_by_creating_your_first_wallet_you_can_organize_your_savings_by_goals_like_kids_emergency_or_long_term_investment')}
                            </p>
                            <Button size="lg" onClick={() => setIsCreating(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 gap-2">
                                <Plus className="w-5 h-5" /> {__('general.create_your_first_wallet')}
                            </Button>
                        </motion.div>
                    )}

                    {/* Wallets Grid */}
                    <motion.div 
                        variants={containerVariants}
                        initial="hidden"
                        animate="show"
                        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <AnimatePresence mode="popLayout">
                            {filteredWallets.map(wallet => (
                                <motion.div 
                                    key={wallet.id} 
                                    layout
                                    variants={itemVariants}
                                    onClick={() => router.get(route('isaas.gold-savers.wallets.show', wallet.id))} 
                                    className="cursor-pointer group h-full"
                                >
                                    <Card className="h-full hover:shadow-md transition-all duration-300">
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <Wallet className="w-5 h-5 text-muted-foreground" />
                                                {wallet.name}
                                            </CardTitle>
                                            <div className="text-xs font-semibold text-muted-foreground mt-1">
                                                {__(wallet.goal_type)}
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <div className="flex justify-between items-end">
                                                <div>
                                                    <div className="text-sm font-medium text-muted-foreground mb-1">{__('general.current_balance')}</div>
                                                    <div className="text-2xl font-bold">{wallet.balance_grams} <span className="text-sm text-muted-foreground font-normal">{__('general.g')}</span></div>
                                                    <div className="text-sm text-muted-foreground mt-1">{formatNumber(wallet.balance_amount)} {wallet.currency}</div>
                                                </div>
                                            </div>

                                            {/* Progress Bar */}
                                            {hasGoalTracking && wallet.target_grams > 0 && (
                                                <div className="mt-4 pt-4 border-t">
                                                    <div className="flex justify-between text-sm mb-2">
                                                        <span className="text-muted-foreground font-medium flex items-center gap-1"><Target className="w-4 h-4"/> {__('general.goal')}</span>
                                                        <span className="font-semibold">{((wallet.balance_grams / wallet.target_grams) * 100).toFixed(1)}%</span>
                                                    </div>
                                                    <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                        <div 
                                                            style={{ width: `${Math.min((wallet.balance_grams / wallet.target_grams) * 100, 100)}%` }}
                                                            className="bg-indigo-600 h-full rounded-full" 
                                                        />
                                                    </div>
                                                    <div className="text-xs text-right text-muted-foreground mt-1">{wallet.target_grams} {__('general.g')} {__('general.target')}</div>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                </motion.div>
                            ))}
                        </AnimatePresence>
                    </motion.div>
                    
                    {wallets.length > 0 && filteredWallets.length === 0 && (
                        <div className="text-center py-12 text-slate-500 bg-white rounded-xl border border-dashed border-slate-200">
                            <Search className="w-8 h-8 mx-auto text-slate-300 mb-3" />
                            <p>{__('general.no_wallets_match_your_filters')}</p>
                            <Button variant="link" onClick={() => { setSearchQuery(''); setGoalFilter('All'); }} className="mt-2">
                                {__('general.clear_filters')}
                            </Button>
                        </div>
                    )}

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
