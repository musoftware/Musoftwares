import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import {
    Receipt,
    FileText,
    Star,
    ArrowUpCircle,
    PlusCircle,
    MinusCircle,
    Lock,
    ChevronRight
} from 'lucide-react';
import Pagination from '@/Components/Pagination';

const TypeIcon = ({ type }) => {
    switch (type) {
        case 'invoice_paid': return <div className="p-2 bg-green-100 rounded-full"><Receipt className="w-5 h-5 text-green-600" /></div>;
        case 'invoice_issued': return <div className="p-2 bg-blue-100 rounded-full"><FileText className="w-5 h-5 text-blue-600" /></div>;
        case 'commission': return <div className="p-2 bg-yellow-100 rounded-full"><Star className="w-5 h-5 text-yellow-600" /></div>;
        case 'withdrawal': return <div className="p-2 bg-orange-100 rounded-full"><ArrowUpCircle className="w-5 h-5 text-orange-600" /></div>;
        case 'manual_credit': return <div className="p-2 bg-emerald-100 rounded-full"><PlusCircle className="w-5 h-5 text-emerald-600" /></div>;
        case 'manual_debit': return <div className="p-2 bg-red-100 rounded-full"><MinusCircle className="w-5 h-5 text-red-600" /></div>;
        default: return <div className="p-2 bg-gray-100 rounded-full"><FileText className="w-5 h-5 text-gray-600" /></div>;
    }
};

export default function Show({ auth, wallet, transactions, lockedBalance }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">My Wallet</h2>}
        >
            <Head title="My Wallet" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

                    {/* Hero Balance Card */}
                    <Card className="bg-slate-900 text-white overflow-hidden border-none shadow-xl">
                        <CardContent className="p-8">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center space-y-6 md:space-y-0">
                                <div>
                                    <p className="text-slate-400 text-sm font-medium mb-1 uppercase tracking-wider">Available Balance</p>
                                    <h1 className="text-5xl md:text-6xl font-bold font-sora tracking-tight">
                                        <CurrencyDisplay amount={wallet.balance} currency={wallet.currency} />
                                    </h1>
                                    <p className="text-slate-400 mt-2 flex items-center font-mono">
                                        = <CurrencyDisplay amount={wallet.balance} currency="USD" /> USD
                                    </p>
                                </div>
                                <div className="flex flex-col space-y-3 w-full md:w-auto">
                                    <Button asChild className="bg-white text-slate-900 hover:bg-slate-200 font-bold px-8 py-6 rounded-xl transition-all hover:scale-105 active:scale-95">
                                        <Link href={route('erp.withdrawals.index')}>
                                            Request Withdrawal <ChevronRight className="ml-2 w-4 h-4" />
                                        </Link>
                                    </Button>

                                    {lockedBalance > 0 && (
                                        <div className="flex items-center justify-center md:justify-start text-orange-400 text-sm font-medium bg-orange-400/10 py-2 px-4 rounded-lg border border-orange-400/20">
                                            <Lock className="w-4 h-4 mr-2" />
                                            <CurrencyDisplay amount={lockedBalance} currency={wallet.currency} /> locked (pending withdrawal)
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Transaction List */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-slate-800 px-1">Recent Transactions</h3>
                        <div className="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                            {transactions.data.map((tx, index) => (
                                <div key={tx.id} className={`p-4 flex items-center justify-between hover:bg-slate-50 transition-colors ${index !== transactions.data.length - 1 ? 'border-b border-slate-100' : ''}`}>
                                    <div className="flex items-center space-x-4">
                                        <TypeIcon type={tx.reference_type} />
                                        <div>
                                            <p className="font-semibold text-slate-900 capitalize leading-tight">
                                                {tx.reference_type?.replace('_', ' ')}
                                            </p>
                                            <p className="text-sm text-slate-500 max-w-[200px] md:max-w-md truncate">
                                                {tx.description}
                                            </p>
                                            <p className="text-[10px] text-slate-400 font-mono mt-0.5">
                                                {new Date(tx.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className={`font-bold font-mono ${tx.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
                                            {tx.type === 'credit' ? '+' : '-'}
                                            <CurrencyDisplay amount={tx.amount} currency={wallet.currency} />
                                        </p>
                                        <p className="text-xs text-slate-400 font-mono">
                                            Bal: <CurrencyDisplay amount={tx.balance_after} currency={wallet.currency} />
                                        </p>
                                    </div>
                                </div>
                            ))}

                            {transactions.data.length === 0 && (
                                <div className="p-12 text-center">
                                    <div className="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-4">
                                        <FileText className="w-8 h-8 text-slate-400" />
                                    </div>
                                    <p className="text-slate-500 font-medium">No transactions found</p>
                                    <p className="text-sm text-slate-400">Your wallet activity will appear here</p>
                                </div>
                            )}
                        </div>

                        <div className="py-4">
                            <Pagination links={transactions.links} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
