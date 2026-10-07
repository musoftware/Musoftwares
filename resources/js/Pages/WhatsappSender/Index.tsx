import { __ } from '@/lib/i18n';
import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Search } from 'lucide-react';
import { toast } from 'sonner';
import { useConfirm } from '@/hooks/useConfirm';

interface Business {
    id: number;
    uuid: string;
    name: string;
    client_name: string | null;
    client_email: string | null;
    client_mobile: string | null;
    client_whatsapp: string | null;
    wallet_balance: string;
    currency: string;
    per_message_fee: string;
    bot_reply_fee?: string;
    accounts_count?: number;
    facebook_client_id?: string | null;
    facebook_client_secret?: string | null;
}

interface Props {
    businesses: Business[];
    apiToken: string;
    filters: {
        search?: string;
        scope?: string;
    };
    isAdmin: boolean;
}

export default function Index({ businesses, apiToken, filters, isAdmin }: Props) {
    const { confirm, confirmDialog } = useConfirm();
    const [search, setSearch] = useState(filters.search || '');
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showRechargeModal, setShowRechargeModal] = useState<Business | null>(null);
    const [showEditModal, setShowEditModal] = useState<Business | null>(null);

    // Create Business form
    const createForm = useForm({
        name: '',
        client_name: '',
        client_email: '',
        client_mobile: '',
        client_whatsapp: '',
        initial_balance: '10.00',
        per_message_fee: '0.0010',
        bot_reply_fee: '0.0005',
        facebook_client_id: '',
        facebook_client_secret: '',
    });

    const handleCreateBusiness = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/whatsapp-sender/businesses', {
            onSuccess: () => {
                createForm.reset();
                setShowCreateModal(false);
            }
        });
    };

    // Edit Business form
    const editForm = useForm({
        name: '',
        client_name: '',
        client_email: '',
        client_mobile: '',
        client_whatsapp: '',
        per_message_fee: '0.0010',
        bot_reply_fee: '0.0005',
        facebook_client_id: '',
        facebook_client_secret: '',
    });

    const triggerEditModal = (biz: Business) => {
        setShowEditModal(biz);
        editForm.setData({
            name: biz.name,
            client_name: biz.client_name || '',
            client_email: biz.client_email || '',
            client_mobile: biz.client_mobile || '',
            client_whatsapp: biz.client_whatsapp || '',
            per_message_fee: biz.per_message_fee,
            bot_reply_fee: biz.bot_reply_fee || '0.0005',
            facebook_client_id: biz.facebook_client_id || '',
            facebook_client_secret: biz.facebook_client_secret || '',
        });
    };

    const handleEditBusiness = (e: React.FormEvent) => {
        e.preventDefault();
        if (!showEditModal) return;
        editForm.put(`/whatsapp-sender/businesses/${showEditModal.id}`, {
            onSuccess: () => {
                editForm.reset();
                setShowEditModal(null);
            }
        });
    };

    // Recharge balance form
    const rechargeForm = useForm({
        amount: '20.00',
    });

    const handleRecharge = (e: React.FormEvent) => {
        e.preventDefault();
        if (!showRechargeModal) return;
        rechargeForm.post(`/whatsapp-sender/businesses/${showRechargeModal.id}/recharge`, {
            onSuccess: () => {
                rechargeForm.reset();
                setShowRechargeModal(null);
            }
        });
    };

    const handleScopeChange = (newScope: 'my' | 'all') => {
        router.get('/whatsapp-sender', { search, scope: newScope }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearch(value);
        router.get('/whatsapp-sender', { search: value, scope: filters.scope || 'my' }, {
            preserveState: true,
            replace: true,
        });
    };

    // Global Statistics calculates
    const totalBalance = businesses.reduce((acc, curr) => acc + parseFloat(curr.wallet_balance), 0);
    const totalAccounts = businesses.reduce((acc, curr) => acc + (curr.accounts_count || 0), 0);

    return (
        <AuthenticatedLayout>
            <Head title={__('whatsapp.sender_index_page_title')} />

            <div className="py-8 px-4 max-w-7xl mx-auto space-y-8">
                {/* Dashboard Header Banner */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight text-zinc-900 dark:text-zinc-50">{__('whatsapp.sender_index_heading')}</h1>
                        <p className="text-zinc-500 dark:text-zinc-400 mt-1 text-sm font-medium">{__('whatsapp.sender_index_subheading')}</p>
                    </div>
                    <button
                        onClick={() => setShowCreateModal(true)}
                        className="bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 font-bold px-5 py-3 rounded-2xl text-sm transition shadow-sm"
                    >
                        {__('whatsapp.sender_index_create_profile_btn')}
                    </button>
                </div>

                {/* Key Summary Stats Widget */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-3xl p-5 shadow-xs">
                        <span className="text-xs text-zinc-500 dark:text-zinc-400 font-semibold block uppercase tracking-wider">{__('whatsapp.sender_index_active_businesses')}</span>
                        <span className="text-3xl font-black text-zinc-900 dark:text-zinc-50 block mt-2">{businesses.length}</span>
                    </div>
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-3xl p-5 shadow-xs">
                        <span className="text-xs text-zinc-500 dark:text-zinc-400 font-semibold block uppercase tracking-wider">{__('whatsapp.sender_index_total_wallet')}</span>
                        <span className="text-3xl font-black text-zinc-900 dark:text-zinc-50 block mt-2">${totalBalance.toFixed(2)} USD</span>
                    </div>
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-3xl p-5 shadow-xs">
                        <span className="text-xs text-zinc-500 dark:text-zinc-400 font-semibold block uppercase tracking-wider">{__('whatsapp.sender_index_connected_waba')}</span>
                        <span className="text-3xl font-black text-zinc-900 dark:text-zinc-50 block mt-2">{__('whatsapp.sender_index_accounts_count', { count: totalAccounts })}</span>
                    </div>
                </div>

                {/* Interactive API Token playground section */}
                <div className="bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/80 rounded-3xl p-6">
                    <h2 className="font-bold text-zinc-900 dark:text-zinc-100 text-lg">{__('whatsapp.sender_index_api_token_title')}</h2>
                    <p className="text-xs text-zinc-500 mt-1">{__('whatsapp.sender_index_api_token_desc')}</p>
                    <div className="mt-4 flex items-center gap-2 max-w-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-2.5 rounded-2xl">
                        <input
                            type="text"
                            readOnly
                            value={apiToken}
                            aria-label={__('whatsapp.sender_index_api_token_title')}
                            className="bg-transparent border-0 ring-0 focus:ring-0 text-xs flex-1 text-zinc-600 dark:text-zinc-300 font-mono"
                        />
                        <button
                            onClick={() => {
                                navigator.clipboard.writeText(apiToken);
                                toast.success(__('whatsapp.sender_index_token_copied'));
                            }}
                            className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-850 dark:text-zinc-200 text-xs px-3.5 py-1.5 rounded-xl font-semibold transition"
                        >
                            {__('general.copy')}
                        </button>
                    </div>
                </div>

                {/* Main Directory searchable table */}
                <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-6">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div className="flex items-center gap-3 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-850 px-4 py-3 rounded-2xl max-w-md w-full">
                            <Search className="w-4 h-4 text-zinc-400" aria-hidden="true" />
                            <input
                                type="text"
                                value={search}
                                onChange={handleSearchChange}
                                placeholder={__('whatsapp.sender_index_search_placeholder')}
                                aria-label={__('whatsapp.sender_index_search_placeholder')}
                                className="bg-transparent border-0 focus:ring-0 text-sm w-full p-0 text-zinc-700 dark:text-zinc-300"
                            />
                        </div>

                        {isAdmin && (
                            <div className="flex items-center bg-zinc-100 dark:bg-zinc-800/60 p-1.5 rounded-2xl text-xs font-semibold self-stretch sm:self-auto">
                                <button
                                    onClick={() => handleScopeChange('my')}
                                    className={`flex-1 sm:flex-initial px-4 py-2 rounded-xl transition ${
                                        (filters.scope || 'my') === 'my'
                                            ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-xs font-bold'
                                            : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                    }`}
                                >
                                    {__('whatsapp.sender_index_scope_my')}
                                </button>
                                <button
                                    onClick={() => handleScopeChange('all')}
                                    className={`flex-1 sm:flex-initial px-4 py-2 rounded-xl transition ${
                                        filters.scope === 'all'
                                            ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-xs font-bold'
                                            : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                    }`}
                                >
                                    {__('whatsapp.sender_index_scope_all')}
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm border-collapse font-sans">
                            <thead>
                                <tr className="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-medium">
                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.sender_index_col_company')}</th>
                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.sender_index_col_client')}</th>
                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.sender_index_col_email')}</th>
                                    <th className="py-3 px-2 font-semibold">{__('general.mobile')}</th>
                                    <th className="py-3 px-2 font-semibold">WhatsApp</th>
                                    {isAdmin && <th className="py-3 px-2 font-semibold">{__('whatsapp.sender_index_col_fees')}</th>}
                                    <th className="py-3 px-2 font-semibold">{__('whatsapp.sender_index_col_wallet')}</th>
                                    <th className="py-3 px-2 font-semibold text-right">{__('general.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {businesses.map(biz => (
                                    <tr key={biz.id} className="border-b border-zinc-100 dark:border-zinc-800/60 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/20">
                                        <td className="py-4 px-2">
                                            <div className="font-bold text-zinc-900 dark:text-zinc-100">{biz.name}</div>
                                            <span className="text-xxs text-zinc-400 dark:text-zinc-500">{__('whatsapp.sender_index_active_numbers', { count: biz.accounts_count || 0 })}</span>
                                        </td>
                                        <td className="py-4 px-2 text-zinc-700 dark:text-zinc-300 font-medium">{biz.client_name || __('general.n_a')}</td>
                                        <td className="py-4 px-2 text-zinc-500 text-xs">{biz.client_email || __('general.n_a')}</td>
                                        <td className="py-4 px-2 text-zinc-500 text-xs font-mono">{biz.client_mobile || __('general.n_a')}</td>
                                        <td className="py-4 px-2 text-zinc-500 text-xs font-mono">{biz.client_whatsapp || __('general.n_a')}</td>
                                        {isAdmin && (
                                            <td className="py-4 px-2">
                                                <div className="text-xs font-bold text-zinc-700 dark:text-zinc-300">{__('whatsapp.sender_index_fee_msg', { amount: parseFloat(biz.per_message_fee).toFixed(4) })}</div>
                                                <div className="text-[10px] font-semibold text-zinc-400 mt-0.5">{__('whatsapp.sender_index_fee_bot', { amount: parseFloat(biz.bot_reply_fee || '0.0005').toFixed(4) })}</div>
                                            </td>
                                        )}
                                        <td className="py-4 px-2">
                                            <span className="font-extrabold text-zinc-900 dark:text-zinc-100">${parseFloat(biz.wallet_balance).toFixed(4)}</span>
                                        </td>
                                        <td className="py-4 px-2 text-right space-x-2.5">
                                            <Link
                                                href={`/whatsapp-sender/businesses/${biz.id}`}
                                                className="bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs font-bold px-4 py-2 rounded-xl transition inline-block"
                                            >
                                                {__('whatsapp.sender_index_manage_workspace')}
                                            </Link>
                                            <button
                                                onClick={() => triggerEditModal(biz)}
                                                className="border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-xs font-semibold px-3 py-2 rounded-xl transition"
                                            >
                                                {__('whatsapp.sender_index_edit_settings')}
                                            </button>
                                            <button
                                                onClick={() => setShowRechargeModal(biz)}
                                                className="border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-xs font-semibold px-3 py-2 rounded-xl transition"
                                            >
                                                {__('whatsapp.sender_index_top_up')}
                                            </button>
                                            <button
                                                onClick={async () => {
                                                    if (!(await confirm({ title: __('whatsapp.sender_index_delete_title'), description: __('whatsapp.sender_index_delete_desc'), variant: 'danger' }))) return;
                                                    router.delete(`/whatsapp-sender/businesses/${biz.id}`);
                                                }}
                                                className="text-red-500 hover:text-red-600 text-xs font-bold"
                                            >
                                                {__('general.delete')}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {businesses.length === 0 && (
                                    <tr>
                                        <td colSpan={isAdmin ? 8 : 7} className="py-8 text-center text-zinc-400">{__('whatsapp.sender_index_empty')}</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Create Business Modal */}
                {showCreateModal && (
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                        <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 max-w-lg w-full rounded-3xl p-6 shadow-2xl space-y-6">
                            <div>
                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.sender_index_create_title')}</h3>
                                <p className="text-xs text-zinc-400 mt-1">{__('whatsapp.sender_index_create_desc')}</p>
                            </div>
                            <form onSubmit={handleCreateBusiness} className="space-y-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_company_name')}</label>
                                    <input
                                        type="text"
                                        required
                                        value={createForm.data.name}
                                        onChange={e => createForm.setData('name', e.target.value)}
                                        placeholder={__('whatsapp.sender_index_company_placeholder')}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_contact_name')}</label>
                                        <input
                                            type="text"
                                            value={createForm.data.client_name}
                                            onChange={e => createForm.setData('client_name', e.target.value)}
                                            placeholder={__('whatsapp.sender_index_contact_placeholder')}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_client_email')}</label>
                                        <input
                                            type="email"
                                            value={createForm.data.client_email}
                                            onChange={e => createForm.setData('client_email', e.target.value)}
                                            placeholder={__('whatsapp.sender_index_email_placeholder')}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_client_mobile')}</label>
                                        <input
                                            type="text"
                                            value={createForm.data.client_mobile}
                                            onChange={e => createForm.setData('client_mobile', e.target.value)}
                                            placeholder={__('whatsapp.sender_index_phone_placeholder')}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_client_whatsapp')}</label>
                                        <input
                                            type="text"
                                            value={createForm.data.client_whatsapp}
                                            onChange={e => createForm.setData('client_whatsapp', e.target.value)}
                                            placeholder={__('whatsapp.sender_index_phone_placeholder')}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_initial_balance')}</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={createForm.data.initial_balance}
                                        onChange={e => createForm.setData('initial_balance', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm"
                                    />
                                    {createForm.errors.initial_balance && (
                                        <span className="text-xs text-red-500 mt-1 block">{createForm.errors.initial_balance}</span>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_meta_app_id')}</label>
                                        <input
                                            type="text"
                                            value={createForm.data.facebook_client_id}
                                            onChange={e => createForm.setData('facebook_client_id', e.target.value)}
                                            placeholder={__('whatsapp.sender_index_app_id_placeholder')}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_meta_app_secret')}</label>
                                        <input
                                            type="password"
                                            value={createForm.data.facebook_client_secret}
                                            onChange={e => createForm.setData('facebook_client_secret', e.target.value)}
                                            placeholder="••••••••"
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                </div>

                                {isAdmin && (
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_per_message_fee')}</label>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                value={createForm.data.per_message_fee}
                                                onChange={e => createForm.setData('per_message_fee', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_bot_reply_fee')}</label>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                value={createForm.data.bot_reply_fee}
                                                onChange={e => createForm.setData('bot_reply_fee', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                    </div>
                                )}

                                <div className="flex justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <button
                                        type="button"
                                        onClick={() => setShowCreateModal(false)}
                                        className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-250 text-xs px-4 py-2 rounded-xl font-bold transition"
                                    >
                                        {__('general.cancel')}
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={createForm.processing}
                                        className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs px-4 py-2 rounded-xl font-bold transition"
                                    >
                                        {__('whatsapp.sender_index_create_submit')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Top-up Balance Modal */}
                {showRechargeModal && (
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                        <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 max-w-sm w-full rounded-3xl p-6 shadow-2xl space-y-6">
                            <div>
                                <h3 className="text-xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{__('whatsapp.sender_index_topup_title', { name: showRechargeModal.name })}</h3>
                                <p className="text-xs text-zinc-400 mt-1">{__('whatsapp.sender_index_topup_desc')}</p>
                            </div>
                            <form onSubmit={handleRecharge} className="space-y-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_topup_amount')}</label>
                                    <input
                                        type="number"
                                        step="0.10"
                                        value={rechargeForm.data.amount}
                                        onChange={e => rechargeForm.setData('amount', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2.5 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                    />
                                    {rechargeForm.errors.amount && (
                                        <span className="text-xs text-red-500 mt-1 block">{rechargeForm.errors.amount}</span>
                                    )}
                                </div>

                                <div className="flex justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <button
                                        type="button"
                                        onClick={() => setShowRechargeModal(null)}
                                        className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-250 text-xs px-4 py-2 rounded-xl font-bold transition"
                                    >
                                        {__('general.cancel')}
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={rechargeForm.processing}
                                        className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs px-4 py-2 rounded-xl font-bold transition"
                                    >
                                        {__('whatsapp.sender_index_topup_submit')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Edit Business Modal */}
                {showEditModal && (
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                        <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 max-w-lg w-full rounded-3xl p-6 shadow-2xl space-y-6">
                            <div>
                                <h3 className="text-xl font-bold tracking-tight text-zinc-955 dark:text-zinc-50">{__('whatsapp.sender_index_edit_title')}</h3>
                                <p className="text-xs text-zinc-400 mt-1">{__('whatsapp.sender_index_edit_desc')}</p>
                            </div>
                            <form onSubmit={handleEditBusiness} className="space-y-4">
                                <div>
                                    <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_company_name')}</label>
                                    <input
                                        type="text"
                                        required
                                        value={editForm.data.name}
                                        onChange={e => editForm.setData('name', e.target.value)}
                                        className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_contact_name')}</label>
                                        <input
                                            type="text"
                                            value={editForm.data.client_name}
                                            onChange={e => editForm.setData('client_name', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_client_email')}</label>
                                        <input
                                            type="email"
                                            value={editForm.data.client_email}
                                            onChange={e => editForm.setData('client_email', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_client_mobile')}</label>
                                        <input
                                            type="text"
                                            value={editForm.data.client_mobile}
                                            onChange={e => editForm.setData('client_mobile', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_client_whatsapp')}</label>
                                        <input
                                            type="text"
                                            value={editForm.data.client_whatsapp}
                                            onChange={e => editForm.setData('client_whatsapp', e.target.value)}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_meta_app_id')}</label>
                                        <input
                                            type="text"
                                            value={editForm.data.facebook_client_id}
                                            onChange={e => editForm.setData('facebook_client_id', e.target.value)}
                                            placeholder={__('whatsapp.sender_index_app_id_placeholder')}
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_meta_app_secret')}</label>
                                        <input
                                            type="password"
                                            value={editForm.data.facebook_client_secret}
                                            onChange={e => editForm.setData('facebook_client_secret', e.target.value)}
                                            placeholder="••••••••"
                                            className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm text-zinc-700 dark:text-zinc-300"
                                        />
                                    </div>
                                </div>

                                {isAdmin && (
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_per_message_fee')}</label>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                value={editForm.data.per_message_fee}
                                                onChange={e => editForm.setData('per_message_fee', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">{__('whatsapp.sender_index_bot_reply_fee')}</label>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                value={editForm.data.bot_reply_fee}
                                                onChange={e => editForm.setData('bot_reply_fee', e.target.value)}
                                                className="w-full bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl py-2 px-3 text-sm font-bold text-zinc-700 dark:text-zinc-300"
                                            />
                                        </div>
                                    </div>
                                )}

                                <div className="flex justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <button
                                        type="button"
                                        onClick={() => setShowEditModal(null)}
                                        className="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-250 text-xs px-4 py-2 rounded-xl font-bold transition"
                                    >
                                        {__('general.cancel')}
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={editForm.processing}
                                        className="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs px-4 py-2 rounded-xl font-bold transition"
                                    >
                                        {__('general.save_changes')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
            {confirmDialog}
        </AuthenticatedLayout>
    );
}
