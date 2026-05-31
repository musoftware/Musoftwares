import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
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

    const itemVariants = {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0, transition: { type: "spring", stiffness: 300, damping: 24 } },
        exit: { opacity: 0, scale: 0.9, transition: { duration: 0.2 } }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-4">{__('My Gold Wallets')}</h2>
                    <GoldSaversTabs />
                </div>
            }
        >
            <Head title={__('Gold Wallets')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header Stats */}
                    {wallets.length > 0 && (
                        <motion.div 
                            initial={{ opacity: 0, y: -20 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"
                        >
                            <Card className="bg-gradient-to-br from-indigo-500 to-purple-600 text-white border-none shadow-lg">
                                <CardContent className="p-6">
                                    <div className="flex items-center gap-4">
                                        <div className="p-3 bg-white/20 rounded-full">
                                            <Scale className="w-6 h-6 text-white" />
                                        </div>
                                        <div>
                                            <p className="text-indigo-100 text-sm font-medium">{__('Total Gold Saved')}</p>
                                            <h3 className="text-3xl font-bold">{totalGrams.toFixed(2)} {__('G')}</h3>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            
                            {hasGoalTracking && (
                                <Card className="bg-white border-indigo-100 shadow-sm relative overflow-hidden">
                                    <CardContent className="p-6">
                                        <div className="flex justify-between items-start mb-2">
                                            <div>
                                                <p className="text-muted-foreground text-sm font-medium">{__('Overall Goals Progress')}</p>
                                                <h3 className="text-2xl font-bold text-slate-800">{overallProgress.toFixed(1)}%</h3>
                                            </div>
                                            <div className="p-2 bg-indigo-50 text-indigo-600 rounded-full">
                                                <Target className="w-5 h-5" />
                                            </div>
                                        </div>
                                        <div className="w-full bg-slate-100 rounded-full h-2 mt-4">
                                            <motion.div 
                                                initial={{ width: 0 }}
                                                animate={{ width: `${Math.min(overallProgress, 100)}%` }}
                                                transition={{ duration: 1, delay: 0.2 }}
                                                className="bg-indigo-600 h-2 rounded-full" 
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            <Card className="bg-white border-slate-200 shadow-sm flex items-center justify-center p-6 border-dashed">
                                <div className="text-center">
                                    <p className="text-muted-foreground mb-4">{__('Active Wallets')}: <span className="font-bold text-indigo-600">{wallets.length}</span></p>
                                    {(!wallets.length || hasMultiWallets) && (
                                        <Button onClick={() => setIsCreating(!isCreating)} className="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                                            <Plus className="w-4 h-4" /> {isCreating ? __('Cancel') : __('Create New Wallet')}
                                        </Button>
                                    )}
                                </div>
                            </Card>
                        </motion.div>
                    )}

                    {/* Filters Bar */}
                    {wallets.length > 0 && (
                        <div className="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                            <div className="relative w-full md:w-96">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                                <Input 
                                    className="pl-9 bg-slate-50 border-slate-200 focus-visible:ring-indigo-500" 
                                    placeholder={__('Search wallets...')}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                            <div className="flex gap-3 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                                <div className="flex items-center gap-2">
                                    <LayoutGrid className="w-4 h-4 text-slate-400" />
                                    <select 
                                        className="border-slate-200 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50"
                                        value={goalFilter}
                                        onChange={(e) => setGoalFilter(e.target.value)}
                                    >
                                        {goalTypes.map(type => (
                                            <option key={type} value={type}>{__(type)}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="flex items-center gap-2">
                                    <ArrowUpDown className="w-4 h-4 text-slate-400" />
                                    <select 
                                        className="border-slate-200 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50"
                                        value={sortBy}
                                        onChange={(e) => setSortBy(e.target.value)}
                                    >
                                        <option value="recent">{__('Recent')}</option>
                                        <option value="balance_desc">{__('Highest Balance')}</option>
                                        <option value="balance_asc">{__('Lowest Balance')}</option>
                                        {hasGoalTracking && <option value="target_progress">{__('Closest to Goal')}</option>}
                                    </select>
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
                                        <CardTitle className="text-lg text-indigo-900">{__('Create New Wallet')}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <form onSubmit={handleCreateWallet} className="flex flex-col md:flex-row gap-4 items-end">
                                            <div className="space-y-2 flex-1 w-full">
                                                <label className="text-sm font-medium text-slate-700">{__('Wallet Name')}</label>
                                                <Input 
                                                    value={newWallet.name} 
                                                    onChange={e => setNewWallet({...newWallet, name: e.target.value})} 
                                                    placeholder={__('e.g., Kids College Fund')}
                                                    className="bg-white"
                                                    required 
                                                />
                                            </div>
                                            <div className="space-y-2 flex-1 w-full">
                                                <label className="text-sm font-medium text-slate-700">{__('Goal Type')}</label>
                                                <select 
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm bg-white"
                                                    value={newWallet.goal_type}
                                                    onChange={e => setNewWallet({...newWallet, goal_type: e.target.value})}
                                                >
                                                    <option value="Investment">{__('Investment')}</option>
                                                    <option value="Wedding">{__('Wedding')}</option>
                                                    <option value="Emergency">{__('Emergency')}</option>
                                                    <option value="Kids">{__('Kids Savings')}</option>
                                                    <option value="Hajj">{__('Hajj / Umrah')}</option>
                                                    <option value="Retirement">{__('Retirement')}</option>
                                                </select>
                                            </div>
                                            {hasGoalTracking && (
                                                <div className="space-y-2 flex-1 w-full">
                                                    <label className="text-sm font-medium text-slate-700">{__('Target Grams')} ({__('Optional')})</label>
                                                    <Input 
                                                        type="number"
                                                        step="0.01"
                                                        value={newWallet.target_grams} 
                                                        onChange={e => setNewWallet({...newWallet, target_grams: e.target.value})} 
                                                        placeholder={__('e.g., 50.00')}
                                                        className="bg-white"
                                                    />
                                                </div>
                                            )}
                                            <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white w-full md:w-auto">{__('Save Wallet')}</Button>
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
                            <h3 className="text-2xl font-bold text-slate-800 mb-2">{__('No Wallets Yet')}</h3>
                            <p className="text-muted-foreground max-w-md mb-8">
                                {__('Start your gold saving journey by creating your first wallet. You can organize your savings by goals like kids, emergency, or long-term investment.')}
                            </p>
                            <Button size="lg" onClick={() => setIsCreating(true)} className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 gap-2">
                                <Plus className="w-5 h-5" /> {__('Create Your First Wallet')}
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
                                    <Card className="h-full hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-100 transition-all duration-300 border-transparent hover:border-indigo-200 bg-white/50 backdrop-blur-sm relative overflow-hidden">
                                        <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-400 to-purple-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <CardHeader className="pb-3 border-b border-slate-100/50">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <CardTitle className="flex items-center gap-2 group-hover:text-indigo-700 transition-colors">
                                                        <div className="p-2 bg-indigo-50 rounded-lg group-hover:bg-indigo-100 transition-colors">
                                                            <Wallet className="w-5 h-5 text-indigo-600 group-hover:scale-110 transition-transform" />
                                                        </div>
                                                        {wallet.name}
                                                    </CardTitle>
                                                    <div className="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-slate-100 text-slate-800 hover:bg-slate-200 mt-3">
                                                        {__(wallet.goal_type)}
                                                    </div>
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="pt-6 space-y-4">
                                            <div className="flex justify-between items-end">
                                                <div>
                                                    <div className="text-sm text-slate-500 font-medium mb-1">{__('Current Balance')}</div>
                                                    <div className="text-3xl font-bold text-slate-800">{wallet.balance_grams} <span className="text-lg text-slate-500 font-normal">{__('G')}</span></div>
                                                    <div className="text-sm text-slate-500 mt-1">{formatNumber(wallet.balance_amount)} {wallet.currency}</div>
                                                </div>
                                            </div>

                                            {/* Progress Bar */}
                                            {hasGoalTracking && wallet.target_grams > 0 && (
                                                <div className="mt-6 pt-6 border-t border-slate-100">
                                                    <div className="flex justify-between text-sm mb-2">
                                                        <span className="text-slate-500 font-medium flex items-center gap-1"><Target className="w-4 h-4"/> {__('Goal')}</span>
                                                        <span className="font-semibold">{((wallet.balance_grams / wallet.target_grams) * 100).toFixed(1)}%</span>
                                                    </div>
                                                    <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                                        <motion.div 
                                                            initial={{ width: 0 }}
                                                            whileInView={{ width: `${Math.min((wallet.balance_grams / wallet.target_grams) * 100, 100)}%` }}
                                                            viewport={{ once: true }}
                                                            transition={{ duration: 1, delay: 0.2 }}
                                                            className="bg-gradient-to-r from-indigo-500 to-indigo-600 h-full rounded-full relative" 
                                                        >
                                                            <div className="absolute inset-0 bg-white/20 w-full h-full animate-[shimmer_2s_infinite]"></div>
                                                        </motion.div>
                                                    </div>
                                                    <div className="text-xs text-right text-slate-400 mt-1">{wallet.target_grams} {__('G')} {__('Target')}</div>
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
                            <p>{__('No wallets match your filters.')}</p>
                            <Button variant="link" onClick={() => { setSearchQuery(''); setGoalFilter('All'); }} className="mt-2">
                                {__('Clear Filters')}
                            </Button>
                        </div>
                    )}

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
