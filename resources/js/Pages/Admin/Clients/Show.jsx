import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Copy, Mail, MessageCircle, ChevronDown, Key, Wallet, FileText, Briefcase, Trash2, Edit } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
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
import AdminNotesPanel from '@/Components/AdminNotesPanel';

export default function Show({ client, wallets }) {
    const [isLoginAsLoading, setIsLoginAsLoading] = useState(false);
    const [isResetPassOpen, setIsResetPassOpen] = useState(false);
    const [newPassword, setNewPassword] = useState('');
    
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
            const res = await window.axios.post(`/admin/clients/${client.id}/login-as`);
            window.location.href = res.data.redirect_url;
        } catch (e) {
            alert('Failed to impersonate user.');
        } finally {
            setIsLoginAsLoading(false);
        }
    };

    const handleResetPassword = async () => {
        try {
            const res = await window.axios.post(`/admin/clients/${client.id}/reset-password`);
            setNewPassword(res.data.new_password);
        } catch (e) {
            alert('Failed to reset password.');
        }
    };

    const submitWalletTx = (e) => {
        e.preventDefault();
        router.post(`/admin/clients/${client.id}/wallet-transaction`, walletTxForm, {
            onSuccess: () => {
                setIsWalletModalOpen(false);
                setWalletTxForm({ ...walletTxForm, amount: '', description: '' });
                alert("Wallet transaction successful!");
            }
        });
    };

    const submitAssignTask = (e) => {
        e.preventDefault();
        router.post(`/admin/clients/${client.id}/tasks`, {}, {
            onSuccess: () => {
                setIsAssignTaskOpen(false);
                alert("Task assigned successfully!");
            }
        });
    };

    const submitSwapBudget = (e) => {
        e.preventDefault();
        router.post(`/admin/clients/${client.id}/swap-budget`, {}, {
            onSuccess: () => {
                setIsSwapBudgetOpen(false);
                alert("Budget swapped successfully!");
            }
        });
    };

    const submitActivateMembership = (e) => {
        e.preventDefault();
        router.post(`/admin/clients/${client.id}/memberships`, {}, {
            onSuccess: () => {
                setIsActivateMembershipOpen(false);
                alert("Membership activated successfully!");
            }
        });
    };

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
        }).format(amount || 0);
    };

    const referralCode = client.referral_code || `${client.name.toUpperCase().replace(/\s+/g, '').substring(0, 5)}2024`;
    const referralLink = `${window.location.origin}/ref/${referralCode}`;

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        alert('Copied to clipboard!');
    };

    return (
        <AdminSidebarLayout title={`User Profile: ${client.name}`} header="User Details">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold font-sora">User Profile</h1>
                
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button className="bg-indigo-600 text-white px-4 py-2 rounded-[8px] hover:bg-indigo-700 transition shadow-sm flex items-center gap-2">
                            Quick Actions <ChevronDown size={16} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuGroup>
                            <DropdownMenuLabel>Account & Tools</DropdownMenuLabel>
                            <DropdownMenuItem onClick={handleLoginAsUser} disabled={isLoginAsLoading}>
                                <Briefcase className="mr-2 h-4 w-4" />
                                <span>{isLoginAsLoading ? 'Logging in...' : 'Login As'}</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/edit`} className="w-full cursor-pointer flex items-center">
                                    <Edit className="mr-2 h-4 w-4" />
                                    <span>Edit Profile</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { setIsResetPassOpen(true); setNewPassword(''); }}>
                                <Key className="mr-2 h-4 w-4" />
                                <span>Reset Password</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsAssignTaskOpen(true)}>
                                <Briefcase className="mr-2 h-4 w-4" />
                                <span>Assign Task</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/files`} className="w-full cursor-pointer">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>Files</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/reports`} className="w-full cursor-pointer">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>Reports</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/clients/${client.id}/referrals`} className="w-full cursor-pointer">
                                    <MessageCircle className="mr-2 h-4 w-4" />
                                    <span>Manage Referrals</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsActivateMembershipOpen(true)}>
                                <Briefcase className="mr-2 h-4 w-4" />
                                <span>Activate Membership</span>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuLabel>Finance</DropdownMenuLabel>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/invoices/create?client_id=${client.id}`} className="w-full cursor-pointer">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>New Invoice</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/invoices?client_id=${client.id}`} className="w-full cursor-pointer">
                                    <FileText className="mr-2 h-4 w-4" />
                                    <span>Invoices</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href={`/admin/finance?client_id=${client.id}`} className="w-full cursor-pointer">
                                    <Wallet className="mr-2 h-4 w-4" />
                                    <span>All Transactions</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'credit' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>Receive Money</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'debit' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>Send Money</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => {
                                setWalletTxForm({ ...walletTxForm, type: 'refund' });
                                setIsWalletModalOpen(true);
                            }}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>Refund Money</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => setIsSwapBudgetOpen(true)}>
                                <Wallet className="mr-2 h-4 w-4" />
                                <span>Swap Budgets</span>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="text-red-600">
                            <Trash2 className="mr-2 h-4 w-4" />
                            <span>Delete User</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            {/* Modals */}
            <Dialog open={isAssignTaskOpen} onOpenChange={setIsAssignTaskOpen}>
                <DialogContent>
                    <form onSubmit={submitAssignTask}>
                        <DialogHeader>
                            <DialogTitle>Assign Task</DialogTitle>
                            <DialogDescription>
                                Create an ERP Task for this client.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-gray-500">This will create a new task named <strong>{client.name}'s Task</strong> and link it to their ERP account.</p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsAssignTaskOpen(false)}>Cancel</Button>
                            <Button type="submit">Create Task</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isSwapBudgetOpen} onOpenChange={setIsSwapBudgetOpen}>
                <DialogContent>
                    <form onSubmit={submitSwapBudget}>
                        <DialogHeader>
                            <DialogTitle>Swap Budgets</DialogTitle>
                            <DialogDescription>
                                Transfer funds from this user's wallet to another user's wallet.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-gray-500 mb-2">Select destination wallet and amount.</p>
                            <div className="space-y-4">
                                <Input type="number" placeholder="Amount to transfer" />
                                <Input placeholder="Target user email or ID" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsSwapBudgetOpen(false)}>Cancel</Button>
                            <Button type="submit">Transfer Funds</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isActivateMembershipOpen} onOpenChange={setIsActivateMembershipOpen}>
                <DialogContent>
                    <form onSubmit={submitActivateMembership}>
                        <DialogHeader>
                            <DialogTitle>Activate Membership</DialogTitle>
                            <DialogDescription>
                                Manually assign a subscription plan to this user.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="text-sm text-gray-500 mb-2">Select a plan from the list below:</p>
                            <select className="border-gray-300 rounded-md w-full">
                                <option>Premium Monthly ($19.99)</option>
                                <option>Enterprise Annual ($499.00)</option>
                            </select>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsActivateMembershipOpen(false)}>Cancel</Button>
                            <Button type="submit">Activate Plan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={isResetPassOpen} onOpenChange={setIsResetPassOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reset Password</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to reset this user's password? A new secure password will be generated.
                        </DialogDescription>
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
                            <Button variant="destructive" onClick={handleResetPassword}>Reset Password</Button>
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
                            <DialogDescription>
                                Add or remove funds directly from the user's platform wallet.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4 space-y-4">
                            <div>
                                <Label>Select Wallet</Label>
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
                                            <Label htmlFor="is_used" className="font-normal cursor-pointer text-gray-700">Mark as Used</Label>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div>
                                <Label>Description / Reason</Label>
                                <Input 
                                    value={walletTxForm.description}
                                    onChange={(e) => setWalletTxForm({ ...walletTxForm, description: e.target.value })}
                                    placeholder="Optional note"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setIsWalletModalOpen(false)}>Cancel</Button>
                            <Button type="submit">Confirm Transaction</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {/* Profile Details */}
                <div className="col-span-1 space-y-6">
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                        <h2 className="text-xl font-bold font-sora mb-4 border-b pb-2">Details</h2>
                        <div className="space-y-3 text-sm">
                            <div>
                                <span className="text-gray-500 block mb-1">Name</span>
                                <span className="font-medium text-gray-900">{client.name}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Email</span>
                                <span className="font-medium text-gray-900">{client.email}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Phone</span>
                                <span className="font-medium text-gray-900">{client.phone || 'N/A'}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block mb-1">Joined</span>
                                <span className="font-medium text-gray-900">{new Date(client.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </div>

                    {/* Referral Section */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                        <h2 className="text-xl font-bold font-sora mb-4 border-b pb-2">Referral Program</h2>

                        <div className="space-y-4">
                            <div>
                                <span className="text-sm text-gray-500 block mb-1">Referral Code:</span>
                                <div className="flex items-center space-x-2">
                                    <span className="font-jetbrains text-indigo-600 bg-indigo-50 px-2 py-1 rounded font-bold tracking-wider">{referralCode}</span>
                                    <button onClick={() => copyToClipboard(referralCode)} className="text-gray-400 hover:text-indigo-600">
                                        <Copy size={16} />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <span className="text-sm text-gray-500 block mb-1">Shareable Link:</span>
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="text"
                                        readOnly
                                        value={referralLink}
                                        className="text-xs border-gray-300 rounded-[4px] w-full bg-gray-50"
                                    />
                                    <button onClick={() => copyToClipboard(referralLink)} className="text-gray-400 hover:text-indigo-600">
                                        <Copy size={16} />
                                    </button>
                                </div>
                            </div>

                            <div className="flex space-x-2 pt-2">
                                <button onClick={() => copyToClipboard(referralLink)} className="flex-1 flex items-center justify-center space-x-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-1.5 rounded-[4px] text-xs transition">
                                    <Copy size={14} /> <span>Copy</span>
                                </button>
                                <a href={`mailto:?subject=Join me&body=Use my referral link: ${referralLink}`} className="flex-1 flex items-center justify-center space-x-1 bg-blue-50 hover:bg-blue-100 text-blue-600 py-1.5 rounded-[4px] text-xs transition">
                                    <Mail size={14} /> <span>Email</span>
                                </a>
                                <a href={`https://wa.me/?text=Join me using my referral link: ${referralLink}`} target="_blank" rel="noopener noreferrer" className="flex-1 flex items-center justify-center space-x-1 bg-green-50 hover:bg-green-100 text-green-600 py-1.5 rounded-[4px] text-xs transition">
                                    <MessageCircle size={14} /> <span>WhatsApp</span>
                                </a>
                            </div>

                            <div className="pt-4 border-t mt-4">
                                <div className="flex justify-between text-sm mb-1">
                                    <span className="text-gray-500">Referred Users:</span>
                                    <span className="font-bold">3</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-500">Total Earned:</span>
                                    <span className="font-bold text-green-600 font-jetbrains">$75.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Wallets and History */}
                <div className="col-span-2 space-y-6">
                    {/* Support Tickets */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                        <h2 className="text-xl font-bold font-sora mb-4 border-b pb-2">Support Tickets</h2>
                        {client.support_tickets && client.support_tickets.length > 0 ? (
                            <ul className="space-y-3">
                                {client.support_tickets.map((ticket) => (
                                    <li
                                        key={ticket.id}
                                        className="flex justify-between border-b pb-2"
                                    >
                                        <span>{ticket.subject}</span>
                                        <span className={`px-2 py-1 text-xs rounded-[4px] ${ticket.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                            {ticket.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-gray-500">
                                No support tickets found.
                            </p>
                        )}
                    </div>

                    {/* Wallets & Transactions */}
                    {wallets.map((wallet) => (
                        <div key={wallet.id} className="bg-white p-6 rounded-[12px] shadow-sm border border-gray-100">
                            <div className="flex justify-between items-end mb-4 border-b pb-2">
                                <div>
                                    <h2 className="text-xl font-bold font-sora">Wallet ({wallet.context})</h2>
                                </div>
                                <span className="text-3xl font-bold text-green-600 font-jetbrains">
                                    {formatCurrency(wallet.balance, wallet.currency)}
                                </span>
                            </div>

                            <h3 className="mb-3 text-sm font-bold tracking-wider text-gray-500 uppercase">
                                Transaction History
                            </h3>
                            {wallet.transactions &&
                            wallet.transactions.length > 0 ? (
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="p-2 font-medium">Date</th>
                                            <th className="p-2 font-medium">Description</th>
                                            <th className="p-2 text-right font-medium">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {wallet.transactions.map((tx) => (
                                            <tr key={tx.id} className="border-t">
                                                <td className="p-2 text-gray-500">{new Date(tx.created_at).toLocaleString()}</td>
                                                <td className="p-2">{tx.description}</td>
                                                <td className={`p-2 text-right font-jetbrains font-medium ${tx.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
                                                    {tx.type === 'credit' ? '+' : '-'}{formatCurrency(tx.amount, wallet.currency)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p className="text-sm text-gray-500">
                                    No transactions found.
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="mt-8">
                <AdminNotesPanel noteableType="App\Models\User" noteableId={client.id} />
            </div>
        </AdminSidebarLayout>
    );
}
