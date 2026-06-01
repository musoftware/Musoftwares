import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Copy, Mail, MessageCircle, ChevronDown, Key, Wallet, FileText, Briefcase, Trash2, Edit, ShieldCheck } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import HiddenAmount from '@/Components/HiddenAmount';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuGroup,
} from "@/Components/ui/dropdown-menu";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export default function Show({ client, stats = {}, wallets, modulePlans = [], subscriptions = [] }) {
    const [isLoginAsLoading, setIsLoginAsLoading] = useState(false);
    const [isResetPassOpen, setIsResetPassOpen] = useState(false);
    const [newPassword, setNewPassword] = useState('');
    const [isChangeRoleOpen, setIsChangeRoleOpen] = useState(false);
    const [selectedRole, setSelectedRole] = useState(client.role || 'client');
    
    // New Modal States
    const [isAssignTaskOpen, setIsAssignTaskOpen] = useState(false);
    const [isSwapBudgetOpen, setIsSwapBudgetOpen] = useState(false);
    const [isActivateMembershipOpen, setIsActivateMembershipOpen] = useState(false);

    const [isWalletModalOpen, setIsWalletModalOpen] = useState(false);
    const [walletTxForm, setWalletTxForm] = useState({
        wallet_id: wallets.length > 0 ? wallets[0].id : '',
        type: 'credit',
        amount: '',
        fee: '',
        is_used: false,
        description: ''
    });

    const handleLoginAsUser = async () => {
        setIsLoginAsLoading(true);
        try {
            const res = await window.axios.post(`/admin/users/${client.id}/login-as`);
            window.location.href = res.data.redirect_url;
        } catch (e) {
            alert('Failed to impersonate user.');
        } finally {
            setIsLoginAsLoading(false);
        }
    };

    const handleResetPassword = async () => {
        try {
            const res = await window.axios.post(`/admin/users/${client.id}/generate-password`);
            setNewPassword(res.data.new_password);
        } catch (e) {
            alert('Failed to reset password.');
        }
    };

    const submitChangeRole = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/update-role`, { role: selectedRole }, {
            onSuccess: () => {
                setIsChangeRoleOpen(false);
                alert(__("general.user_role_updated_successfully"));
            }
        });
    };

    const submitWalletTx = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/wallet-transaction`, walletTxForm, {
            onSuccess: () => {
                setIsWalletModalOpen(false);
                setWalletTxForm({ ...walletTxForm, amount: '', description: '' });
                alert("Wallet transaction successful!");
            }
        });
    };

    const [taskForm, setTaskForm] = useState({ title: '', description: '' });
    const submitAssignTask = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/tasks`, taskForm, {
            onSuccess: () => {
                setIsAssignTaskOpen(false);
                setTaskForm({ title: '', description: '' });
                alert("Task assigned successfully!");
            }
        });
    };

    const [swapForm, setSwapForm] = useState({ amount: '', target_user_id: '' });
    const submitSwapBudget = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/swap-budget`, swapForm, {
            onSuccess: () => {
                setIsSwapBudgetOpen(false);
                setSwapForm({ amount: '', target_user_id: '' });
                alert("Budget swapped successfully!");
            }
        });
    };

    const [membershipForm, setMembershipForm] = useState({ 
        object: modulePlans.length > 0 ? modulePlans[0].id : '', 
        duration_days: '1' 
    });
    const submitActivateMembership = (e) => {
        e.preventDefault();
        router.post(`/admin/users/${client.id}/membership`, membershipForm, {
            onSuccess: () => {
                setIsActivateMembershipOpen(false);
                setMembershipForm({ object: modulePlans.length > 0 ? modulePlans[0].id : '', duration_days: '1' });
                alert("Membership activated successfully!");
            }
        });
    };

    const [isEditMembershipOpen, setIsEditMembershipOpen] = useState(false);
    const [editMembershipForm, setEditMembershipForm] = useState({
        id: '',
        status: 'active',
        expires_at: ''
    });

    const openEditMembership = (sub) => {
        setEditMembershipForm({
            id: sub.id,
            status: sub.status,
            expires_at: sub.expires_at ? sub.expires_at.split('T')[0] : ''
        });
        setIsEditMembershipOpen(true);
    };

    const submitEditMembership = (e) => {
        e.preventDefault();
        router.put(`/admin/users/${client.id}/membership/${editMembershipForm.id}`, editMembershipForm, {
            onSuccess: () => {
                setIsEditMembershipOpen(false);
                alert("Membership updated successfully!");
            }
        });
    };

    const deleteMembership = (subId) => {
        if (confirm("Are you sure you want to delete this subscription?")) {
            router.delete(`/admin/users/${client.id}/membership/${subId}`, {
                onSuccess: () => {
                    alert("Membership deleted successfully!");
                }
            });
        }
    };

    const referralCode = client.slug || client.id;
    const referralLink = `${window.location.origin}/r/${referralCode}`;

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        alert('Copied to clipboard!');
    };

    return (
        <AdminSidebarLayout title={`User Profile: ${client.name}`} header="User Details">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold font-sora">{__('general.user_profile')}</h1>
                
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                    <div role="button" className="inline-flex cursor-pointer items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-[8px] hover:bg-slate-800 transition shadow-sm text-sm font-semibold select-none">{__('general.quick_actions')}<ChevronDown size={16} />
                    </div>
                </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuGroup>
                            <DropdownMenuLabel>{__('general.account_tools')}</DropdownMenuLabel>
                            <DropdownMenuItem onClick={handleLoginAsUser} disabled={isLoginAsLoading}>
                                <Briefcase className="mr-2 h-4 w-4" />
                                <span>{isLoginAsLoading ? 'Logging in...' : 'Login As'}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/edit`} className="w-full cursor-pointer flex items-center">
                                    <Edit className="mr-2 h-4 w-4" />
                                    <span>{__('general.edit_profile')}</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { setIsResetPassOpen(true); setNewPassword(''); }}>
                                <Key className="mr-2 h-4 w-4" />
                                <span>{__('general.reset_password')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { setSelectedRole(client.role || 'client'); setIsChangeRoleOpen(true); }}>
                                <ShieldCheck className="mr-2 h-4 w-4" />
                                <span>{__('general.change_role')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsAssignTaskOpen(true)}>
                                <Briefcase className="mr-2 h-4 w-4" />
                                <span>{__('general.assign_task')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/files`} className="w-full cursor-pointer flex items-center">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>Files</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/reports`} className="w-full cursor-pointer flex items-center">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>Reports</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/users/${client.id}/referrals`} className="w-full cursor-pointer flex items-center">
                                    <MessageCircle className="mr-2 h-4 w-4" />
                                    <span>{__('general.manage_referrals')}</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsActivateMembershipOpen(true)}>
                                <Briefcase className="mr-2 h-4 w-4" />
                                <span>{__('general.activate_membership')}</span>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuLabel>Finance</DropdownMenuLabel>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/invoices/create?client_id=${client.id}`} className="w-full cursor-pointer flex items-center">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>{__('general.new_invoice')}</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/invoices?client_id=${client.id}`} className="w-full cursor-pointer flex items-center">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>Invoices</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/finance?client_id=${client.id}`} className="w-full cursor-pointer flex items-center">
                                    <Wallet className="mr-2 h-4 w-4" />
                                    <span>{__('general.all_transactions')}</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'receive' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>{__('general.receive_money')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'send-money' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>{__('general.send_money')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'earn' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>{__('general.earned_money')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'charge' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>{__('general.charge_account')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'refund' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>{__('general.refund_money')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsSwapBudgetOpen(true)}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>{__('general.swap_budgets')}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <a href={`/admin/users/${client.id}/balance-sheet`} target="_blank" rel="noopener noreferrer" className="w-full cursor-pointer flex items-center">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>{__('general.due_balance_sheet')}</span>
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="text-red-600">
                            <Trash2 className="mr-2 h-4 w-4" />
                            <span>{__('general.delete_user')}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            {/* Modals */}
            <Dialog open={isAssignTaskOpen} onOpenChange={setIsAssignTaskOpen}>
                <DialogContent>
                    <form onSubmit={submitAssignTask}>
                        <DialogHeader>
                            <DialogTitle>{__('general.assign_task')}</DialogTitle>
                            <DialogDescription>{__('general.create_an_erp_task_for_this_client')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-gray-500">{__('general.this_will_create_a_new_task_named')}<strong>{client.name}'s Task</strong>{__('general.and_link_it_to_their_erp_account')}</p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsAssignTaskOpen(false)}>Cancel</Button>
                            <Button type="submit">{__('general.create_task')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isSwapBudgetOpen} onOpenChange={setIsSwapBudgetOpen}>
                <DialogContent>
                    <form onSubmit={submitSwapBudget}>
                        <DialogHeader>
                            <DialogTitle>{__('general.swap_budgets')}</DialogTitle>
                            <DialogDescription>{__('general.transfer_funds_from_this_user_s_wallet_to_another_user_s_wallet')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-gray-500 mb-2">{__('general.select_destination_wallet_and_amount')}</p>
                            <div className="space-y-4">
                                <Input type="number" placeholder={__('general.amount_to_transfer')} />
                                <Input placeholder={__('general.target_user_email_or_id')} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsSwapBudgetOpen(false)}>Cancel</Button>
                            <Button type="submit">{__('general.transfer_funds')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isActivateMembershipOpen} onOpenChange={setIsActivateMembershipOpen}>
                <DialogContent>
                    <form onSubmit={submitActivateMembership}>
                        <DialogHeader>
                            <DialogTitle>{__('general.activate_membership')}</DialogTitle>
                            <DialogDescription>{__('general.manually_assign_a_subscription_plan_to_this_user')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label>{__('general.select_plan')}</Label>
                                <select 
                                    className="border-gray-300 rounded-md w-full mt-1"
                                    value={membershipForm.plan_id}
                                    onChange={e => setMembershipForm({...membershipForm, plan_id: e.target.value})}
                                    required
                                >
                                    {modulePlans.map(plan => (
                                        <option key={plan.id} value={plan.id}>{plan.name} - {plan.module}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Duration (Days)</Label>
                                <Input 
                                    type="number" 
                                    min="1" 
                                    value={membershipForm.duration_days}
                                    onChange={e => setMembershipForm({...membershipForm, duration_days: e.target.value})}
                                    required 
                                    className="mt-1"
                                />
                                <p className="text-xs text-gray-500 mt-1">{__('general.e_g_enter_1_for_a_1_day_test')}</p>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsActivateMembershipOpen(false)}>Cancel</Button>
                            <Button type="submit">{__('general.activate_plan')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isChangeRoleOpen} onOpenChange={setIsChangeRoleOpen}>
                <DialogContent>
                    <form onSubmit={submitChangeRole}>
                        <DialogHeader>
                            <DialogTitle>{__("general.change_user_role")}</DialogTitle>
                            <DialogDescription>
                                {__("general.change_direct_permissions_and_role_access_level_for_this_user")}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label>{__("general.select_role")}</Label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 mt-2"
                                    value={selectedRole}
                                    onChange={e => setSelectedRole(e.target.value)}
                                    required
                                >
                                    <option value="client">{__("Client")}</option>
                                    <option value="user">{__("User")}</option>
                                    <option value="admin">{__("Admin")}</option>
                                    <option value="manager">{__("general.manager")}</option>
                                    <option value="employee">{__("Employee")}</option>
                                    <option value="moderator">{__("general.moderator")}</option>
                                </select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsChangeRoleOpen(false)}>{__("Cancel")}</Button>
                            <Button type="submit">{__("general.update_role")}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isResetPassOpen} onOpenChange={setIsResetPassOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('general.reset_password')}</DialogTitle>
                        <DialogDescription>{__('general.are_you_sure_you_want_to_reset_this_user_s_password_a_new_secure_password_will_be_generated')}</DialogDescription>
                    </DialogHeader>
                    {newPassword ? (
                        <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                            <p className="text-sm text-green-800 mb-2">Password reset successful! Provide this to the user:</p>
                            <div className="font-mono text-lg font-bold bg-white p-2 rounded flex justify-between items-center">
                                {newPassword}
                                <Button variant="ghost" size="sm" onClick={() => copyToClipboard(newPassword)}><Copy size={14}/></Button>
                            </div>
                        </div>
                    ) : (
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsResetPassOpen(false)}>Cancel</Button>
                            <Button variant="destructive" onClick={handleResetPassword}>{__('general.reset_password')}</Button>
                        </DialogFooter>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog open={isWalletModalOpen} onOpenChange={setIsWalletModalOpen}>
                <DialogContent>
                    <form onSubmit={submitWalletTx}>
                        <DialogHeader>
                            <DialogTitle>
                                {walletTxForm.type === 'credit' ? 'Receive Money' : 'Send Money'}
                            </DialogTitle>
                            <DialogDescription>{__('general.add_or_remove_funds_directly_from_the_user_s_platform_wallet')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label>{__('general.select_wallet')}</Label>
                                <select 
                                    className="w-full mt-1 rounded-md border-gray-300"
                                    value={walletTxForm.wallet_id}
                                    onChange={(e) => setWalletTxForm({ ...walletTxForm, wallet_id: e.target.value })}
                                    required
                                >
                                    {wallets.map(w => (
                                        <option key={w.id} value={w.id}>{w.context} ({w.currency}) - Balance: {w.balance}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Amount</Label>
                                <Input 
                                    type="number" 
                                    step="0.01"
                                    value={walletTxForm.amount}
                                    onChange={(e) => setWalletTxForm({ ...walletTxForm, amount: e.target.value })}
                                    required
                                />
                            </div>
                            
                            {(walletTxForm.type === 'credit' || walletTxForm.type === 'refund') && (
                                <div className="flex gap-4">
                                    <div className="flex-1">
                                        <Label>Gateway Fee (optional)</Label>
                                        <Input 
                                            type="number" 
                                            step="0.01"
                                            value={walletTxForm.fee}
                                            onChange={(e) => setWalletTxForm({ ...walletTxForm, fee: e.target.value })}
                                            placeholder="0.00"
                                        />
                                    </div>
                                    {walletTxForm.type === 'credit' && (
                                        <div className="flex items-center space-x-2 pt-6">
                                            <input 
                                                type="checkbox" 
                                                id="is_used" 
                                                checked={walletTxForm.is_used}
                                                onChange={(e) => setWalletTxForm({ ...walletTxForm, is_used: e.target.checked })}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <Label htmlFor="is_used" className="font-normal cursor-pointer text-gray-700">{__('general.mark_as_used')}</Label>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div>
                                <Label>{__('general.description_reason')}</Label>
                                <Input 
                                    value={walletTxForm.description}
                                    onChange={(e) => setWalletTxForm({ ...walletTxForm, description: e.target.value })}
                                    placeholder={__('general.optional_note')}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsWalletModalOpen(false)}>Cancel</Button>
                            <Button type="submit">{__('general.confirm_transaction')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>


            <Dialog open={isEditMembershipOpen} onOpenChange={setIsEditMembershipOpen}>
                <DialogContent>
                    <form onSubmit={submitEditMembership}>
                        <DialogHeader>
                            <DialogTitle>{__('general.edit_membership')}</DialogTitle>
                            <DialogDescription>{__('general.modify_the_subscription_status_and_expiration_date')}</DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label>Status</Label>
                                <select 
                                    className="border-gray-300 rounded-md w-full mt-1"
                                    value={editMembershipForm.status}
                                    onChange={e => setEditMembershipForm({...editMembershipForm, status: e.target.value})}
                                    required
                                >
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div>
                                <Label>{__('general.expires_at')}</Label>
                                <Input 
                                    type="date" 
                                    value={editMembershipForm.expires_at}
                                    onChange={e => setEditMembershipForm({...editMembershipForm, expires_at: e.target.value})}
                                    required 
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsEditMembershipOpen(false)}>Cancel</Button>
                            <Button type="submit">{__('general.save_changes')}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* NEW HERO SECTION */}
            <div className="bg-white p-8 rounded-[12px] shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row items-center md:items-start gap-6 text-center md:text-left">
                <div className="w-24 h-24 rounded-full bg-slate-900 text-white flex items-center justify-center text-3xl font-bold font-sora shadow-md shrink-0">
                    {client.initials || "U"}
                </div>
                <div className="flex-1">
                    <h2 className="text-2xl font-bold font-sora text-slate-900 mb-1">{client.name}</h2>
                    <p className="text-slate-500 mb-4">{client.email}</p>
                    <div className="flex flex-wrap gap-2 justify-center md:justify-start">
                        <span className="px-3 py-1 bg-slate-100 text-slate-800 rounded-full text-xs font-bold uppercase tracking-wide border border-slate-200">
                            ID: {client.id}
                        </span>
                        <span className="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold uppercase tracking-wide border border-indigo-200">
                            Role: {client.role || 'client'}
                        </span>
                        {client.kyc_verified ? (
                            <span className="px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-bold uppercase tracking-wide border border-green-200">{__('general.kyc_verified')}</span>
                        ) : (
                            <span className="px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold uppercase tracking-wide border border-amber-200">
                                Unverified
                            </span>
                        )}
                        <span className="px-3 py-1 bg-slate-50 text-slate-500 rounded-full text-xs uppercase tracking-wide border border-slate-200">
                            Last Active: {client.last_activity_at ? new Date(client.last_activity_at).toLocaleDateString() : "Never"}
                        </span>
                    </div>
                </div>
            </div>

            {/* NEW ACTIVITY OVERVIEW GRID */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.invoices_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Invoices</div>
                    <div className="text-xs text-green-600 font-medium mt-1">{stats.invoices_paid || 0} Paid</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.tickets_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Tickets</div>
                    <div className="text-xs text-amber-600 font-medium mt-1">{stats.tickets_open || 0} Open</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.orders_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Orders</div>
                    <div className="text-xs text-slate-400 font-medium mt-1">Total</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.services_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Services</div>
                    <div className="text-xs text-green-600 font-medium mt-1">{stats.services_approved || 0} Approved</div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {/* Left Column: Personal Info & Referral */}
                <div className="col-span-1 space-y-6">
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <Briefcase size={18} className="text-slate-400" />{__('general.personal_information')}</h2>
                        <div className="space-y-4 text-sm">
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Currency</span><span className="font-medium text-slate-900 break-words">{client.currency || <span className="text-slate-400 italic">Default</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Hour Rate (USD)</span><span className="font-medium text-slate-900 break-words">{client.hour_rate || "0.00"}</span></div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Phone</span><span className="font-medium text-slate-900 break-words">{client.phone || <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">WhatsApp</span><span className="font-medium text-slate-900 break-words">{client.whatsapp_number || <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Skype</span><span className="font-medium text-slate-900 break-words">{client.skype || <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Facebook</span><span className="font-medium text-slate-900 break-words">{client.facebook || <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Job</span><span className="font-medium text-slate-900 break-words">{client.job || <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Joined</span><span className="font-medium text-slate-900">{client.created_at ? new Date(client.created_at).toLocaleDateString() : "N/A"}</span></div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">{__('general.start_date')}</span><span className="font-medium text-slate-900">{client.date_start ? new Date(client.date_start).toLocaleDateString() : <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">{__('general.end_date')}</span><span className="font-medium text-slate-900">{client.date_end ? new Date(client.date_end).toLocaleDateString() : <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span></div>
                            </div>
                            <div>
                                <span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Address</span>
                                <span className="font-medium text-slate-900 break-words">{client.address || <span className="text-slate-400 italic">{__('general.not_provided')}</span>}</span>
                            </div>
                            <div className="pt-4 border-t border-slate-100">
                                <div className="grid grid-cols-2 gap-2">
                                    <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Taxable</span><span className="font-medium text-slate-900">{client.client_taxable ? "Yes" : "No"}</span></div>
                                    <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">{__('general.invoice_taxable')}</span><span className="font-medium text-slate-900">{client.invoice_taxable ? "Yes" : "No"}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <MessageCircle size={18} className="text-slate-400" />{__('general.referral_program')}</h2>
                        <div className="space-y-4">
                            <div>
                                <span className="text-sm text-slate-500 block mb-1">Referral Code:</span>
                                <div className="flex items-center space-x-2">
                                    <span className="font-jetbrains text-slate-900 bg-slate-100 px-2 py-1 rounded font-bold tracking-wider border border-slate-200">{referralCode}</span>
                                </div>
                            </div>
                            <div>
                                <span className="text-sm text-slate-500 block mb-1">Shareable Link:</span>
                                <div className="flex items-center space-x-2">
                                    <input type="text" readOnly value={referralLink} className="text-xs border-slate-300 rounded-[4px] w-full bg-slate-50 text-slate-600" />
                                    <button onClick={() => copyToClipboard(referralLink)} className="p-2 border border-slate-300 rounded-[4px] hover:bg-slate-50 transition">
                                        <Copy size={14} className="text-slate-600" />
                                    </button>
                                </div>
                            </div>
                            <div className="pt-4 border-t border-slate-100">
                                <div className="flex justify-between text-sm mb-2">
                                    <span className="text-slate-500">Referral Enabled:</span>
                                    <span className="font-bold text-slate-900">{client.allow_referral_system ? "Yes" : "No"}</span>
                                </div>
                                <div className="flex justify-between text-sm mb-2">
                                    <span className="text-slate-500">Commission %:</span>
                                    <span className="font-bold text-slate-900">{client.affiliate_commission_percentage || "0.00"}%</span>
                                </div>
                                <div className="flex justify-between text-sm mb-2">
                                    <span className="text-slate-500">Add to Total:</span>
                                    <span className="font-bold text-slate-900">{client.add_commission_to_total ? "Yes" : "No"}</span>
                                </div>
                                {client.ref_user_id && (
                                    <div className="flex justify-between text-sm mb-2">
                                        <span className="text-slate-500">Referred By (ID):</span>
                                        <span className="font-bold text-slate-900">#{client.ref_user_id}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-sm mb-1">
                                    <span className="text-slate-500">Total Referrals:</span>
                                    <span className="font-bold text-slate-900">{client.referrals_count || 0}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Column: Financial & Wallets */}
                <div className="col-span-2 space-y-6">
                    {/* Financial Summary */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <Wallet size={18} className="text-slate-400" />{__('general.financial_summary')}</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.available_balance')}</span>
                                <span className="font-bold text-slate-900 font-jetbrains">{formatCurrency(client.available_balance || 0, client.currency)}</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.unpaid_invoices')}</span>
                                <span className="font-bold text-red-600 font-jetbrains">{formatCurrency(stats.invoices_unpaid_sum || 0, client.currency)}</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.total_spend')}</span>
                                <HiddenAmount 
                                    amount={formatCurrency((client.total_paid || 0) - (client.total_cost || 0), client.currency)}
                                    hiddenText={__("general.hidden")}
                                />
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Remaining</span>
                                <span className="font-bold text-green-600 font-jetbrains">{formatCurrency((client.available_balance || 0) - (stats.invoices_unpaid_sum || 0), client.currency)}</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.pending_comm')}</span>
                                <span className="font-bold text-amber-600 font-jetbrains">{formatCurrency(client.pending_commission || 0, client.currency)}</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.work_time')}</span>
                                <span className="font-bold text-slate-900">0h 0m</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.invoiced_days')}</span>
                                <span className="font-bold text-slate-900">0 days</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">{__('general.reward_points')}</span>
                                <span className="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold text-xs">0</span>
                            </div>
                        </div>
                    </div>

                    {/* Mail Sequence */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200 mb-6">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <Mail size={18} className="text-slate-400" />{__('general.mail_sequence')}</h2>
                        {client.active_mail_sequence ? (
                            <div>
                                <div className="p-3 bg-green-50 border border-green-200 rounded-[8px] flex items-center gap-3 mb-4">
                                    <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                        <MessageCircle size={16} />
                                    </div>
                                    <div>
                                        <div className="font-bold text-green-900">{client.active_mail_sequence.name || "Active Sequence"}</div>
                                        <div className="text-xs text-green-700">Current Step: {client.active_mail_sequence.step || 1}</div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div>
                                <div className="p-3 bg-slate-50 border border-slate-200 rounded-[8px] flex items-center gap-3 mb-4 text-slate-500">
                                    <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                        <Mail size={16} />
                                    </div>
                                    <div className="text-sm font-medium">{__('general.no_active_mail_sequence')}</div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Subscription / Memberships */}
                    {client.subscription_date && (
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <Briefcase size={18} className="text-slate-400" />{__('general.active_subscription')}</h2>
                        <div className="flex justify-between items-center">
                            {new Date(client.subscription_date) > new Date() ? (() => {
                                const daysRemaining = Math.max(0, Math.ceil((new Date(client.subscription_date) - new Date()) / (1000 * 60 * 60 * 24)));
                                const percentage = Math.min(100, Math.max(0, (daysRemaining / 30) * 100)); // Assuming 30 days plan for display
                                const dashArray = 2 * Math.PI * 52;
                                const dashOffset = dashArray - ((percentage / 100) * dashArray);
                                
                                return (
                                    <div className="w-full text-center">
                                        <div className="relative w-[120px] h-[120px] mx-auto mb-4">
                                            <svg viewBox="0 0 120 120" className="w-full h-full transform -rotate-90">
                                                <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" strokeWidth="8" />
                                                <circle 
                                                    cx="60" cy="60" r="52" fill="none" stroke="#10b981" strokeWidth="8"
                                                    strokeDasharray={dashArray} strokeDashoffset={dashOffset} strokeLinecap="round"
                                                    className="transition-all duration-1000 ease-out"
                                                />
                                            </svg>
                                            <div className="absolute inset-0 flex flex-col items-center justify-center">
                                                <span className="text-2xl font-bold text-slate-900 font-jetbrains">{daysRemaining}</span>
                                                <span className="text-xs text-slate-500 uppercase font-bold">Days</span>
                                            </div>
                                        </div>
                                        <div className="text-slate-900 font-bold mb-1">{client.subscription_plan || "Custom Plan"}</div>
                                        <div className="text-sm text-slate-500">Expires: {new Date(client.subscription_date).toLocaleDateString()}</div>
                                    </div>
                                );
                            })() : (
                                <div className="w-full text-center py-6">
                                    <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
                                        <Trash2 size={24} />
                                    </div>
                                    <h5 className="text-red-600 font-bold text-lg mb-1">Expired</h5>
                                    <p className="text-sm text-slate-500">{__('general.subscription_has_ended')}</p>
                                </div>
                            )}
                        </div>
                    </div>
                    )}

                    {/* User Subscriptions List */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <Briefcase size={18} className="text-slate-400" />{__('general.user_subscriptions')}</h2>
                        {subscriptions && subscriptions.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th className="p-3 font-bold text-slate-600">Module</th>
                                            <th className="p-3 font-bold text-slate-600">Status</th>
                                            <th className="p-3 font-bold text-slate-600">{__('general.expires_at')}</th>
                                            <th className="p-3 text-right font-bold text-slate-600">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {subscriptions.map((sub) => (
                                            <tr key={sub.id} className="border-b border-slate-100 hover:bg-slate-50">
                                                <td className="p-3 font-medium text-slate-900">
                                                    {modulePlans.find(p => p.id === sub.object)?.name || sub.object}
                                                </td>
                                                <td className="p-3">
                                                    <span className={`px-2 py-1 text-xs font-bold uppercase rounded-full ${
                                                        sub.status === 'active' ? 'bg-green-100 text-green-800' :
                                                        sub.status === 'expired' ? 'bg-red-100 text-red-800' :
                                                        'bg-amber-100 text-amber-800'
                                                    }`}>
                                                        {sub.status}
                                                    </span>
                                                </td>
                                                <td className="p-3 text-slate-500">
                                                    {sub.expires_at ? new Date(sub.expires_at).toLocaleDateString() : 'Lifetime'}
                                                </td>
                                                <td className="p-3 text-right space-x-2">
                                                    <Button variant="ghost" size="sm" onClick={() => openEditMembership(sub)}>
                                                        Edit
                                                    </Button>
                                                    <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-800 hover:bg-red-50" onClick={() => deleteMembership(sub.id)}>
                                                        Delete
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-slate-500 italic p-4 bg-slate-50 rounded-md">{__('general.no_module_subscriptions_found')}</p>
                        )}
                    </div>

                    {/* Wallets & Transactions */}
                    {wallets.map((wallet) => (
                        <div key={wallet.id} className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200">
                            <div className="flex justify-between items-end mb-4 border-b pb-2">
                                <div>
                                    <h2 className="text-xl font-bold font-sora text-slate-900">Wallet ({wallet.context})</h2>
                                </div>
                                <span className="text-3xl font-bold text-slate-900 font-jetbrains">
                                    {formatCurrency(wallet.balance, wallet.currency)}
                                </span>
                            </div>

                            <h3 className="mb-3 text-sm font-bold tracking-wider text-slate-500 uppercase">{__('general.transaction_history')}</h3>
                            {wallet.transactions && wallet.transactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th className="p-3 font-bold text-slate-600">Date</th>
                                            <th className="p-3 font-bold text-slate-600">Description</th>
                                            <th className="p-3 text-right font-bold text-slate-600">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {wallet.transactions.map((tx) => (
                                            <tr key={tx.id} className="border-b border-slate-100 hover:bg-slate-50">
                                                <td className="p-3 text-slate-500">{new Date(tx.created_at).toLocaleString()}</td>
                                                <td className="p-3 text-slate-900">{tx.description}</td>
                                                <td className={`p-3 text-right font-jetbrains font-medium ${tx.type === 'credit' ? 'text-green-600' : 'text-slate-900'}`}>
                                                    {formatCurrency(tx.type === 'credit' ? Math.abs(tx.amount) : -Math.abs(tx.amount), wallet.currency)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                </div>
                            ) : (
                                <p className="text-sm text-slate-500 italic p-4 bg-slate-50 rounded-md">{__('general.no_transactions_found_in_this_wallet')}</p>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="mt-8 bg-white p-6 rounded-lg border border-slate-200 flex flex-col items-center justify-center space-y-4">
                <div className="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-500">
                    <ShieldCheck size={24} />
                </div>
                <div className="text-center">
                    <h3 className="text-lg font-bold text-slate-900 font-sora mb-1">{__('general.secure_notes')}</h3>
                    <p className="text-sm text-slate-500 mb-4 max-w-md mx-auto">{__('general.view_and_manage_end_to_end_encrypted_notes_passwords_and_sensitive_information_for_this_user')}</p>
                    <Link 
                        href={`/admin/users/${client.id}/notes`}
                        className="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white font-semibold rounded-lg hover:bg-slate-800 transition"
                    >{__('general.open_secure_notes')}</Link>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
