import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import React, { useState, useMemo, useEffect } from 'react';
import {
    LayoutDashboard,
    Users,
    Briefcase,
    CheckSquare,
    FileText,
    Receipt,
    FileCode,
    History,
    Sparkles,
    Calendar as CalendarIcon,
    LifeBuoy,
    Activity,
    Settings,
    Plus,
    Search,
    ChevronRight,
    ArrowUpRight,
    Pin,
    Folder,
    Clock,
    UserCheck,
    CreditCard,
    DollarSign,
    Filter,
    ShieldCheck,
    CheckCircle2,
    CalendarDays,
    Trash2,
    MessageSquare,
    Eye,
    TrendingUp,
    TrendingDown,
    Layers,
    Share2,
    Inbox,
    FileSpreadsheet,
    CornerDownRight,
    FileLock,
    Send,
    Edit2,
    Check,
    X,
    UserPlus,
    Sliders,
    AlertCircle,
    Cloud, Database, Link as LinkIcon, HardDrive, Key, CheckCircle, SearchCode, Lock
} from 'lucide-react';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { useToast } from '@/Components/ui/use-toast';
import { cn, formatDate, formatMoney } from '@/lib/utils';

// Shared UI components from existing design system
import { DataTable } from '@/Components/ui/DataTable';
import { EmptyState } from '@/Components/ui/EmptyState';
import { StatCard } from '@/Components/ui/StatCard';
import { PageHeader } from '@/Components/ui/PageHeader';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

import { SectionCard } from '@/Components/ui/SectionCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';

// High-fidelity Financial Amount display using standard font-mono
export function FinancialAmount({ amount, currency = 'USD', colorize = false }: { amount: number; currency?: string; colorize?: boolean }) {
    const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    const isPositive = numericAmount > 0;
    
    let colorClass = 'text-slate-900';
    if (colorize) {
        if (numericAmount > 0) colorClass = 'text-emerald-600';
        else if (numericAmount < 0) colorClass = 'text-rose-600';
    }

    return (
        <span className={`font-mono font-semibold text-[13px] tracking-tight ${colorClass}`}>
            {colorize && isPositive ? '+' : ''}
            {new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(numericAmount)}
        </span>
    );
}

// Activity Timeline with sleek vertical indicator
export function ActivityTimeline({ items }: { items: Array<any> }) {
    const getIconForAction = (action: string) => {
        if (!action) return <Activity className="h-3.5 w-3.5 text-slate-400" />;
        const a = action.toLowerCase();
        if (a.includes('invoice_paid')) return <DollarSign className="h-3.5 w-3.5 text-emerald-500" />;
        if (a.includes('invoice')) return <FileText className="h-3.5 w-3.5 text-indigo-500" />;
        if (a.includes('client')) return <Users className="h-3.5 w-3.5 text-blue-500" />;
        if (a.includes('project')) return <Briefcase className="h-3.5 w-3.5 text-amber-500" />;
        if (a.includes('ticket')) return <LifeBuoy className="h-3.5 w-3.5 text-rose-500" />;
        if (a.includes('wallet_credit')) return <ArrowUpRight className="h-3.5 w-3.5 text-emerald-500" />;
        if (a.includes('wallet_debit')) return <CornerDownRight className="h-3.5 w-3.5 text-rose-500" />;
        return <Activity className="h-3.5 w-3.5 text-slate-400" />;
    };

    return (
        <div className="relative border-l border-slate-200 pl-6 ml-3 space-y-6 py-2">
            {items.map((item, idx) => (
                <div key={idx} className="relative">
                    <span className="absolute -left-[31px] top-1 bg-white border border-slate-200 rounded-full p-1 flex items-center justify-center shadow-sm">
                        {item.icon || getIconForAction(item.title)}
                    </span>
                    <div>
                        <div className="flex items-center justify-between text-xs mb-1">
                            <span className="font-semibold text-slate-700 capitalize">{item.title?.replace(/_/g, ' ')}</span>
                            <span className="text-slate-400 font-mono">{item.time}</span>
                        </div>
                        <p className="text-[13px] text-slate-500 leading-relaxed">{item.description}</p>
                        {item.user && (
                            <span className="text-[11px] bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-full mt-1.5 inline-block font-medium">
                                By {item.user}
                            </span>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

interface ERPDashboardProps {
    stats?: {
        totalRevenue: number;
        outstandingRevenue: number;
        clientCount: number;
        recurringCount: number;
        growthPercent: number | null;
        businessCurrency: string;
    };
    clients?: Array<{
        id: number;
        name: string;
        company: string;
        email: string;
        phone: string;
        address: string;
        currency: string;
        totalInvoiced: number;
        totalPaid: number;
    }>;
    invoices?: Array<{
        id: number;
        invoiceNumber: string;
        clientName: string;
        amount: number;
        currency: string;
        issuedDate: string;
        dueDate: string;
        status: string;
        project: string;
    }>;
    chartData?: Array<{
        name: string;
        Sales: number;
        Costs: number;
    }>;
    projects?: Array<any>;
    supportTickets?: Array<any>;
    activityLogs?: Array<any>;
    upcomingBookings?: Array<any>;
}

export default function ERPDashboard({ stats: serverStats, clients: serverClients, invoices: serverInvoices, chartData: serverChartData, projects: serverProjects, supportTickets: serverTickets, activityLogs: serverActivityLogs, upcomingBookings: serverBookings }: ERPDashboardProps) {
    const { toast } = useToast();
    const [currentSection, setCurrentSection] = useState('overview');

    // ────────────────────────────────────────────────────────
    // BACKEND HYDRATION & CORE STATES
    // ────────────────────────────────────────────────────────
    const stats = serverStats || {
        totalRevenue: 0,
        outstandingRevenue: 0,
        clientCount: 0,
        recurringCount: 0,
        growthPercent: null,
        businessCurrency: 'USD',
    };
    const currency = stats.businessCurrency || 'USD';

    const [activeClients, setActiveClients] = useState<Array<any>>(serverClients || []);
    const [activeInvoices, setActiveInvoices] = useState<Array<any>>(serverInvoices || []);
    const [selectedClient, setSelectedClient] = useState<any>(null);
    const [showAddClientModal, setShowAddClientModal] = useState(false);
    const [showEditClientModal, setShowEditClientModal] = useState(false);
    const [showWalletModal, setShowWalletModal] = useState(false);

    // Form inputs for Client operations
    const [clientForm, setClientForm] = useState({
        name: '',
        email: '',
        phone: '',
        address: '',
        currency: 'USD'
    });
    
    // Form inputs for Wallet adjustment operations
    const [walletForm, setWalletForm] = useState({
        amount: '',
        type: 'credit', // credit, debit, lock, unlock
        note: ''
    });

    // ────────────────────────────────────────────────────────
    // WORKSPACE SIMULATED DB SEEDS (High fidelity interactive fallbacks)
    // ────────────────────────────────────────────────────────
    const [projects, setProjects] = useState<Array<any>>(serverProjects && serverProjects.length > 0 ? serverProjects : [
        { id: 1, name: 'Acme Corporate Redesign', client: 'Acme Corp Solutions', status: 'Active', budget: 15000, deadline: '2026-08-30', progress: 65, leader: 'Sarah Lin' },
        { id: 2, name: 'Mobile Banking App SOW', client: 'Globex Financials', status: 'Planning', budget: 28000, deadline: '2026-10-15', progress: 15, leader: 'Alex Rivera' },
        { id: 3, name: 'SEO & Growth Campaign', client: 'Nexus Tech Inc', status: 'Completed', budget: 4500, deadline: '2026-05-10', progress: 100, leader: 'John Doe' },
        { id: 4, name: 'Cloud Infrastructure Upgrade', client: 'Cyberdyne Systems', status: 'On Hold', budget: 9200, deadline: '2026-07-20', progress: 40, leader: 'Jane Doe' }
    ]);
    const [newProjectForm, setNewProjectForm] = useState({
        name: '', client: '', budget: '', deadline: '', leader: '', status: 'Planning'
    });
    const [showAddProjectModal, setShowAddProjectModal] = useState(false);

    const [tasks, setTasks] = useState<Array<any>>([
        { id: 1, title: 'Draft MSA Contract revisions', category: 'Todo', priority: 'High', assignee: 'Sarah', due: '2026-05-25' },
        { id: 2, title: 'Reconcile Q1 invoices ledger', category: 'In Progress', priority: 'Urgent', assignee: 'John', due: '2026-05-20' },
        { id: 3, title: 'Client presentation wireframes', category: 'In Review', priority: 'Medium', assignee: 'Jane', due: '2026-05-22' },
        { id: 4, title: 'Configure payment gateway api keys', category: 'Done', priority: 'High', assignee: 'Alex', due: '2026-05-15' },
        { id: 5, title: 'Set up auto-invoice email templates', category: 'Todo', priority: 'Low', assignee: 'Sarah', due: '2026-06-01' }
    ]);
    const [quickTaskTitle, setQuickTaskTitle] = useState('');

    const [expenses, setExpenses] = useState<Array<any>>([
        { id: 1, title: 'Vercel Serverless Hosting', category: 'Software', amount: 150, date: '2026-05-02', status: 'Paid', receipt: 'Receipt_Vercel.pdf' },
        { id: 2, title: 'GitHub Enterprise Workspace Seats', category: 'Software', amount: 84, date: '2026-05-05', status: 'Paid', receipt: 'Receipt_GitHub.pdf' },
        { id: 3, title: 'Corporate Flight Tickets (SaaS Summit)', category: 'Travel', amount: 620, date: '2026-05-12', status: 'Pending', receipt: 'Flight_Acme.pdf' },
        { id: 4, title: 'Client Onboarding Dinner', category: 'Meals', amount: 195, date: '2026-05-14', status: 'Reimbursed', receipt: 'Dinner_Nexus.pdf' }
    ]);
    const [expenseForm, setExpenseForm] = useState({ title: '', category: 'Software', amount: '', date: '', status: 'Pending' });
    const [showAddExpenseModal, setShowAddExpenseModal] = useState(false);

    const [storageProviders, setStorageProviders] = useState<Array<any>>([
        { id: 1, name: 'AWS S3 Core', driver: 's3', bucket: 'musoftware-erp-prod', status: 'Connected', isDefault: true },
        { id: 2, name: 'Cloudflare R2 Backup', driver: 's3', bucket: 'erp-archive-r2', status: 'Connected', isDefault: false }
    ]);
    const [showAddProviderModal, setShowAddProviderModal] = useState(false);
    const [providerForm, setProviderForm] = useState({ name: '', driver: 's3', bucket: '', key: '', secret: '', endpoint: '', region: '' });

    const [documents, setDocuments] = useState<Array<any>>([
        { id: 1, name: 'MSA_Acme_Corp_Signed.pdf', size: '2.4 MB', type: 'Contract', date: '2026-05-01', uploadedBy: 'Sarah Lin', provider: 'AWS S3 Core', tags: ['Legal', 'Client'] },
        { id: 2, name: 'Receipt_Vercel_Hosting.pdf', size: '142 KB', type: 'Receipt', date: '2026-05-03', uploadedBy: 'Jane Doe', provider: 'Cloudflare R2 Backup', tags: ['Expense'] },
        { id: 3, name: 'Globex_App_SOW_Draft.docx', size: '890 KB', type: 'Proposal', date: '2026-05-08', uploadedBy: 'Alex Rivera', provider: 'AWS S3 Core', tags: ['Draft'] },
        { id: 4, name: 'W9_Tax_Document.pdf', size: '1.1 MB', type: 'Tax', date: '2026-04-12', uploadedBy: 'John Doe', provider: 'AWS S3 Core', tags: ['Tax', 'Internal'] }
    ]);
    const [showAddDocModal, setShowAddDocModal] = useState(false);
    const [docForm, setDocForm] = useState({ name: '', type: 'Contract', provider: 'AWS S3 Core' });

    const [contracts, setContracts] = useState<Array<any>>([
        { id: 1, title: 'Master Services Agreement (MSA)', client: 'Acme Corp Solutions', status: 'Signed', date: '2026-05-01', value: 15000 },
        { id: 2, title: 'Non-Disclosure Agreement (NDA)', client: 'Globex Financials', status: 'Sent to Client', date: '2026-05-10', value: 0 },
        { id: 3, title: 'Statement of Work (SOW) - Phase 2', client: 'Nexus Tech Inc', status: 'Draft', date: '2026-05-16', value: 8500 }
    ]);
    const [showAddContractModal, setShowAddContractModal] = useState(false);
    const [contractForm, setContractForm] = useState({ title: '', client: '', value: '', status: 'Draft' });

    const [notes, setNotes] = useState<Array<any>>([
        { id: 1, title: 'Invoicing Terms for Q3', category: 'Internal', content: 'Ensure all enterprise clients are billed on Net-15 starting next quarter. Apply a 2.5% discount for payments made via Client Wallet directly.', pinned: true, date: '2026-05-15' },
        { id: 2, title: 'Globex Mobile App Specifications', category: 'Project', content: 'Requirements list:\n- Biometric auth login\n- Fast wallet linking\n- Multi-currency currency charts.', pinned: false, date: '2026-05-10' },
        { id: 3, title: 'Acme Corp Meeting Log', category: 'Client', content: 'Reviewed timeline and outstanding bills. Client agreed to pay via manual credit adjustment.', pinned: false, date: '2026-05-05' }
    ]);
    const [selectedNote, setSelectedNote] = useState<any>(notes[0]);
    const [noteEditor, setNoteEditor] = useState({ title: notes[0]?.title || '', content: notes[0]?.content || '', category: notes[0]?.category || 'Internal' });

    const [supportTickets, setSupportTickets] = useState<Array<any>>(serverTickets && serverTickets.length > 0 ? serverTickets : [
        { id: 101, title: 'Invoice #INV-2900 double charged', client: 'Acme Corp Solutions', priority: 'High', status: 'Open', date: '2026-05-17' },
        { id: 102, title: 'Unable to upload receipts in panel', client: 'Globex Financials', priority: 'Medium', status: 'In Progress', date: '2026-05-16' },
        { id: 103, title: 'Need multi-currency billing enabled', client: 'Nexus Tech Inc', priority: 'Low', status: 'Resolved', date: '2026-05-12' }
    ]);
    const [newTicketForm, setNewTicketForm] = useState({ title: '', client: '', priority: 'Medium' });
    const [showAddTicketModal, setShowAddTicketModal] = useState(false);

    const [teamMembers] = useState<Array<any>>([
        { id: 1, name: 'Sarah Lin', email: 'sarah@musoftware.com', role: 'Owner', status: 'Active', activities: 41 },
        { id: 2, name: 'John Doe', email: 'john@musoftware.com', role: 'Manager', status: 'Active', activities: 29 },
        { id: 3, name: 'Jane Doe', email: 'jane@musoftware.com', role: 'Accountant', status: 'Active', activities: 18 },
        { id: 4, name: 'Alex Rivera', email: 'alex@musoftware.com', role: 'Staff', status: 'Away', activities: 9 }
    ]);

    const [activityLogs, setActivityLogs] = useState<Array<any>>(serverActivityLogs && serverActivityLogs.length > 0 ? serverActivityLogs : [
        { title: 'Invoice #INV-4929 Issued', time: '10 mins ago', description: 'Simulated Invoice #INV-4929 sent directly to Globex Financials', user: 'Jane Doe' },
        { title: 'New Client created', time: '1 hour ago', description: 'Acme Corp Solutions client record established with active USD currency', user: 'Sarah Lin' },
        { title: 'Project milestone finalized', time: '3 hours ago', description: 'Sleek design system completed for Acme Corporate Redesign', user: 'Alex Rivera' },
        { title: 'Expense reported', time: '1 day ago', description: 'GitHub Enterprise Seats subscription recorded', user: 'John Doe' }
    ]);

    const [settingsForm, setSettingsForm] = useState({
        workspaceName: stats.clientCount > 0 ? "Musoftware Enterprise Workspace" : "Quiet SaaS Operations Hub",
        taxRate: '14.00',
        defaultCurrency: 'USD',
        autoReminder: true,
        strictPCI: true
    });

    // ────────────────────────────────────────────────────────
    // BACKEND HANDLERS (Interacts with Laravel DB via Inertia)
    // ────────────────────────────────────────────────────────
    const handleAddClient = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('erp.clients.store'), clientForm, {
            onSuccess: () => {
                setShowAddClientModal(false);
                setClientForm({ name: '', email: '', phone: '', address: '', currency: 'USD' });
                toast({ description: 'Client record saved to database.' });
                // Log activity
                prependActivity('Client Created', `Created client record ${clientForm.name} in relational ledger.`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    const handleEditClient = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedClient) return;
        router.put(route('erp.clients.update', selectedClient.id), clientForm, {
            onSuccess: () => {
                setShowEditClientModal(false);
                setSelectedClient(null);
                toast({ description: 'Client updated successfully.' });
                prependActivity('Client Updated', `Updated billing information for ${clientForm.name}.`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    const handleDeleteClient = (client: any) => {
        if (confirm(`Are you sure you want to delete client ${client.name}? This will remove all linked wallets.`)) {
            router.delete(route('erp.clients.destroy', client.id), {
                onSuccess: () => {
                    toast({ description: 'Client deleted successfully.' });
                    prependActivity('Client Deleted', `Deleted client record ${client.name} permanently.`);
                }
            });
        }
    };

    const handleWalletAdjustment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedClient) return;

        let endpoint = route('erp.wallet.credit', selectedClient.id);
        if (walletForm.type === 'debit') endpoint = route('erp.wallet.debit', selectedClient.id);
        else if (walletForm.type === 'lock') endpoint = route('erp.wallet.lock', selectedClient.id);
        else if (walletForm.type === 'unlock') endpoint = route('erp.wallet.unlock', selectedClient.id);

        router.post(endpoint, {
            amount: parseFloat(walletForm.amount),
            note: walletForm.note
        }, {
            onSuccess: () => {
                setShowWalletModal(false);
                setWalletForm({ amount: '', type: 'credit', note: '' });
                toast({ description: `Wallet ${walletForm.type} operation completed.` });
                prependActivity('Wallet Adjusted', `Performed manual ${walletForm.type} of $${walletForm.amount} for ${selectedClient.name}.`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    // ────────────────────────────────────────────────────────
    // WORKSPACE DYNAMIC STATE HANDLERS
    // ────────────────────────────────────────────────────────
    const prependActivity = (title: string, description: string) => {
        setActivityLogs(prev => [
            { title, time: 'Just now', description, user: 'You (Owner)' },
            ...prev
        ]);
    };

    // Sync selected note content changes back to list
    useEffect(() => {
        if (!selectedNote) return;
        setNotes(prev => prev.map(n => n.id === selectedNote.id ? { ...n, ...noteEditor } : n));
    }, [noteEditor]);

    // Handle note selection
    const handleSelectNote = (note: any) => {
        setSelectedNote(note);
        setNoteEditor({ title: note.title, content: note.content, category: note.category });
    };

    // Tasks Quick Add
    const handleQuickAddTask = (category: string) => {
        if (!quickTaskTitle.trim()) return;
        const newTask = {
            id: tasks.length + 1,
            title: quickTaskTitle,
            category,
            priority: 'Medium',
            assignee: 'Sarah',
            due: new Date(Date.now() + 86400000 * 3).toISOString().split('T')[0]
        };
        setTasks(prev => [...prev, newTask]);
        setQuickTaskTitle('');
        prependActivity('Task Created', `Added operational task: "${newTask.title}" directly to lane "${category}"`);
        toast({ description: 'Task added.' });
    };

    // Move task category
    const moveTask = (taskId: number, direction: 'forward' | 'backward') => {
        const lanes = ['Todo', 'In Progress', 'In Review', 'Done'];
        setTasks(prev => prev.map(t => {
            if (t.id === taskId) {
                const currentIdx = lanes.indexOf(t.category);
                let newIdx = currentIdx;
                if (direction === 'forward' && currentIdx < 3) newIdx++;
                if (direction === 'backward' && currentIdx > 0) newIdx--;
                
                const newLane = lanes[newIdx];
                if (newLane !== t.category) {
                    prependActivity('Task Progressed', `Moved task "${t.title}" from "${t.category}" to "${newLane}"`);
                }
                return { ...t, category: newLane };
            }
            return t;
        }));
    };

    // Log Expense
    const handleAddExpense = (e: React.FormEvent) => {
        e.preventDefault();
        const newExp = {
            id: expenses.length + 1,
            title: expenseForm.title,
            category: expenseForm.category,
            amount: parseFloat(expenseForm.amount),
            date: expenseForm.date || new Date().toISOString().split('T')[0],
            status: expenseForm.status,
            receipt: 'Uploaded_Receipt.pdf'
        };
        setExpenses(prev => [...prev, newExp]);
        setShowAddExpenseModal(false);
        setExpenseForm({ title: '', category: 'Software', amount: '', date: '', status: 'Pending' });
        prependActivity('Expense Logged', `Logged corporate expense: ${newExp.title} ($${newExp.amount})`);
        toast({ description: 'Expense recorded successfully.' });
    };

    // Add Storage Provider
    const handleAddProvider = (e: React.FormEvent) => {
        e.preventDefault();
        const newProv = {
            id: storageProviders.length + 1,
            name: providerForm.name,
            driver: providerForm.driver,
            bucket: providerForm.bucket,
            status: 'Connected',
            isDefault: storageProviders.length === 0
        };
        setStorageProviders(prev => [...prev, newProv]);
        setShowAddProviderModal(false);
        setProviderForm({ name: '', driver: 's3', bucket: '', key: '', secret: '', endpoint: '', region: '' });
        prependActivity('Storage Integration', `Connected ${newProv.name} (${newProv.driver}) bucket ${newProv.bucket} to Workspace.`);
        toast({ description: 'Storage Provider connected successfully.' });
    };

    // Add Document
    const handleAddDoc = (e: React.FormEvent) => {
        e.preventDefault();
        const newDoc = {
            id: documents.length + 1,
            name: docForm.name.endsWith('.pdf') ? docForm.name : `${docForm.name}.pdf`,
            size: '412 KB',
            type: docForm.type,
            date: new Date().toISOString().split('T')[0],
            uploadedBy: 'You',
            provider: docForm.provider,
            tags: [docForm.type]
        };
        setDocuments(prev => [...prev, newDoc]);
        setShowAddDocModal(false);
        setDocForm({ name: '', type: 'Contract', provider: storageProviders[0]?.name || 'Local' });
        prependActivity('Direct Cloud Upload', `Stored asset: ${newDoc.name} directly to ${newDoc.provider}`);
        toast({ description: 'Asset securely transmitted to cloud storage.' });
    };

    // Add Project
    const handleAddProject = (e: React.FormEvent) => {
        e.preventDefault();
        const newProj = {
            id: projects.length + 1,
            name: newProjectForm.name,
            client: newProjectForm.client,
            status: newProjectForm.status,
            budget: parseFloat(newProjectForm.budget),
            deadline: newProjectForm.deadline,
            progress: 0,
            leader: newProjectForm.leader || 'Sarah Lin'
        };
        setProjects(prev => [...prev, newProj]);
        setShowAddProjectModal(false);
        setNewProjectForm({ name: '', client: '', budget: '', deadline: '', leader: '', status: 'Planning' });
        prependActivity('Project Created', `Initiated Workspace Project: "${newProj.name}" for client "${newProj.client}"`);
        toast({ description: 'Project established.' });
    };

    // Draft Contract
    const handleAddContract = (e: React.FormEvent) => {
        e.preventDefault();
        const newCont = {
            id: contracts.length + 1,
            title: contractForm.title,
            client: contractForm.client,
            status: contractForm.status,
            date: new Date().toISOString().split('T')[0],
            value: parseFloat(contractForm.value) || 0
        };
        setContracts(prev => [...prev, newCont]);
        setShowAddContractModal(false);
        setContractForm({ title: '', client: '', value: '', status: 'Draft' });
        prependActivity('Contract Initiated', `Drafted contract: "${newCont.title}" ($${newCont.value})`);
        toast({ description: 'Agreement generated.' });
    };

    // Add Support Ticket
    const handleAddTicket = (e: React.FormEvent) => {
        e.preventDefault();
        const newTicket = {
            id: supportTickets.length + 101,
            title: newTicketForm.title,
            client: newTicketForm.client,
            priority: newTicketForm.priority,
            status: 'Open',
            date: new Date().toISOString().split('T')[0]
        };
        setSupportTickets(prev => [...prev, newTicket]);
        setShowAddTicketModal(false);
        setNewTicketForm({ title: '', client: '', priority: 'Medium' });
        prependActivity('Support Ticket Filed', `Workspace ticket registered: "${newTicket.title}"`);
        toast({ description: 'Support request recorded.' });
    };

    // Pin/Unpin Note
    const togglePinNote = (noteId: number) => {
        setNotes(prev => prev.map(n => n.id === noteId ? { ...n, pinned: !n.pinned } : n));
        if (selectedNote && selectedNote.id === noteId) {
            setSelectedNote((prev: any) => ({ ...prev, pinned: !prev.pinned }));
        }
    };

    // Add empty Note
    const handleCreateNote = () => {
        const newN = {
            id: notes.length + 1,
            title: 'Untitled Draft Note',
            category: 'Internal',
            content: 'Write something here...',
            pinned: false,
            date: new Date().toISOString().split('T')[0]
        };
        setNotes(prev => [newN, ...prev]);
        setSelectedNote(newN);
        setNoteEditor({ title: newN.title, content: newN.content, category: newN.category });
        prependActivity('Note Created', `Created empty workspace scratchpad note.`);
    };

    // ────────────────────────────────────────────────────────
    // WORKSPACE SIDEBAR SECTIONS REGISTRY
    // ────────────────────────────────────────────────────────
    const menuItems = [
        { id: 'overview', label: 'Overview', icon: LayoutDashboard },
        { id: 'clients', label: 'Clients', icon: Users, badge: activeClients.length },
        { id: 'projects', label: 'Projects', icon: Briefcase, badge: projects.filter(p => p.status === 'Active').length },
        { id: 'tasks', label: 'Tasks', icon: CheckSquare, badge: tasks.filter(t => t.category !== 'Done').length },
        { id: 'invoices', label: 'Invoices', icon: FileText, badge: activeInvoices.length },
        { id: 'transactions', label: 'Transactions', icon: History },
        { id: 'documents', label: 'Files', icon: Folder },
        { id: 'notes', label: 'Notes', icon: Pin },
        { id: 'calendar', label: 'Calendar', icon: CalendarIcon },
        { id: 'team', label: 'Team', icon: UserCheck },
        { id: 'settings', label: 'Settings', icon: Settings },
    ];

    const activeMenuLabel = useMemo(() => {
        return menuItems.find(item => item.id === currentSection)?.label || 'Workspace';
    }, [currentSection]);

    return (
        <AuthenticatedLayout header="Workspace">
            <Head title={`Workspace — ${activeMenuLabel}`} />

            <div className="max-w-[1400px] mx-auto pb-12 font-sans px-4 sm:px-6 lg:px-8 mt-6">
                
                {/* Contextual Breadcrumb Navigator */}
                <div className="flex items-center gap-1.5 text-sm text-slate-500 mb-8">
                    <span className="hover:text-slate-900 cursor-pointer transition-colors">Workspace</span>
                    <ChevronRight className="h-4 w-4 shrink-0 text-slate-400" />
                    <span className="text-slate-900 font-medium">{activeMenuLabel}</span>
                </div>

                <div className="flex flex-col lg:flex-row gap-8 items-start">
                    
                    {/* ────────────────────────────────────────────────────────
                        LEFT WORKSPACE SECONDARY NAVIGATION
                        ──────────────────────────────────────────────────────── */}
                    <aside className="w-full lg:w-64 shrink-0">
                        <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-4">
                            <div className="flex items-center gap-2 px-3 pb-3 border-b border-slate-100">
                                <div className="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-indigo-100">
                                    M
                                </div>
                                <div className="min-w-0">
                                    <span className="font-semibold text-sm text-slate-900 block truncate">
                                        {settingsForm.workspaceName}
                                    </span>
                                    <span className="text-[11px] text-slate-400 font-mono block">
                                        Active Tenant ID: #{serverStats ? '9012' : 'DRAFT'}
                                    </span>
                                </div>
                            </div>

                            <nav className="space-y-0.5">
                                {menuItems.map((item) => {
                                    const Icon = item.icon;
                                    const isActive = currentSection === item.id;
                                    return (
                                        <button
                                            key={item.id}
                                            onClick={() => setCurrentSection(item.id)}
                                            className={`w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg transition-all group ${
                                                isActive
                                                ? 'bg-slate-100 text-slate-900 font-medium'
                                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                                            }`}
                                        >
                                            <div className="flex items-center gap-3">
                                                <Icon className={`h-4 w-4 shrink-0 transition-colors ${
                                                    isActive ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600'
                                                }`} />
                                                <span>{item.label}</span>
                                            </div>
                                            {item.badge !== undefined && item.badge > 0 && (
                                                <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${
                                                    isActive ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'bg-slate-100 text-slate-500'
                                                }`}>
                                                    {item.badge}
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>
                    </aside>

                    {/* ────────────────────────────────────────────────────────
                        RIGHT WORKSPACE DYNAMIC CONTENT AREA
                        ──────────────────────────────────────────────────────── */}
                    <div className="flex-1 w-full min-w-0">
                        
                        {/* 1. OVERVIEW (DASHBOARD) */}
                        {currentSection === 'overview' && (
                            <div className="space-y-10">
                                
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">Overview</h2>
                                        <p className="text-sm text-slate-500 mt-1">Here's what's happening in your workspace today.</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Button size="sm" variant="outline" className="shadow-sm border-slate-200" onClick={() => setShowAddClientModal(true)}>
                                            <UserPlus className="mr-2 h-4 w-4" /> Add Client
                                        </Button>
                                        <Link 
                                            href={route('erp.invoices.create')}
                                            className={cn(buttonVariants({ size: 'sm' }), "shadow-sm")}
                                        >
                                            <Plus className="mr-2 h-4 w-4" /> New Invoice
                                        </Link>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                    <StatCard 
                                        label="Total Revenue"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(stats.totalRevenue)}
                                        icon={DollarSign}
                                    />
                                    <StatCard 
                                        label="Outstanding"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(stats.outstandingRevenue)}
                                        icon={Clock}
                                    />
                                    <StatCard 
                                        label="Active Clients"
                                        value={activeClients.length}
                                        icon={Users}
                                    />
                                    <StatCard 
                                        label="Subscriptions"
                                        value={stats.recurringCount}
                                        icon={Layers}
                                    />
                                </div>

                                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                    
                                    {/* Main Content Column */}
                                    <div className="lg:col-span-2 space-y-8">
                                        
                                        <SectionCard title="Active Projects" action={<button onClick={() => setCurrentSection('projects')} className="text-sm text-primary hover:underline transition-colors">View all</button>}>
                                            <div className="space-y-3">
                                                {projects.filter(p => p.status === 'Active' || p.status === 'Planning').slice(0, 3).map((proj) => (
                                                    <div key={proj.id} className="group border border-border p-4 rounded-xl hover:bg-surface-raised transition-all cursor-pointer" onClick={() => setCurrentSection('projects')}>
                                                        <div className="flex items-start justify-between">
                                                            <div>
                                                                <h4 className="font-medium text-text-primary text-sm group-hover:text-primary transition-colors">{proj.name}</h4>
                                                                <p className="text-sm text-text-muted mt-1">{proj.client}</p>
                                                            </div>
                                                            <div className="text-right">
                                                                <span className="text-sm font-medium text-text-primary">{proj.progress}%</span>
                                                            </div>
                                                        </div>
                                                        <div className="w-full bg-border/40 h-1.5 rounded-full overflow-hidden mt-4">
                                                            <div className="bg-primary h-full rounded-full transition-all" style={{ width: `${proj.progress}%` }} />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </SectionCard>

                                        <SectionCard title="Recent Invoices" noPadding action={<button onClick={() => setCurrentSection('invoices')} className="text-sm text-primary hover:underline transition-colors">View all</button>}>
                                            <div className="divide-y divide-border/40">
                                                {activeInvoices.length === 0 ? (
                                                    <EmptyState 
                                                        icon={FileText}
                                                        title="No recent invoices"
                                                    />
                                                ) : (
                                                    activeInvoices.slice(0, 4).map((inv) => (
                                                        <div key={inv.id} className="p-4 hover:bg-surface-raised transition-colors flex items-center justify-between">
                                                            <div className="flex items-center gap-4">
                                                                <div className="h-10 w-10 rounded-full bg-surface-raised flex items-center justify-center text-text-secondary">
                                                                    <FileText className="h-4 w-4" />
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-text-primary text-sm block">{inv.clientName}</span>
                                                                    <span className="text-text-muted block text-xs mt-0.5">{inv.invoiceNumber} • Due {formatDate(inv.dueDate)}</span>
                                                                </div>
                                                            </div>
                                                            <div className="text-right flex flex-col items-end gap-1">
                                                                <FinancialAmount amount={inv.amount} currency={inv.currency} />
                                                                <StatusBadge status={inv.status} size="sm" />
                                                            </div>
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        </SectionCard>
                                    </div>

                                    {/* Sidebar Column */}
                                    <div className="space-y-8">
                                        
                                        {/* Quick Actions */}
                                        <div>
                                            <h3 className="text-sm font-semibold text-slate-900 mb-4">Quick Actions</h3>
                                            <div className="space-y-2">
                                                <button onClick={() => setShowAddClientModal(true)} className="w-full flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm transition-all text-left group">
                                                    <div className="flex items-center gap-3">
                                                        <div className="bg-slate-50 p-2 rounded-lg group-hover:bg-white transition-colors">
                                                            <UserPlus className="h-4 w-4 text-slate-600" />
                                                        </div>
                                                        <span className="font-medium text-slate-700 text-sm">Add Client</span>
                                                    </div>
                                                    <ChevronRight className="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                                </button>
                                                <button onClick={() => setShowAddProjectModal(true)} className="w-full flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm transition-all text-left group">
                                                    <div className="flex items-center gap-3">
                                                        <div className="bg-slate-50 p-2 rounded-lg group-hover:bg-white transition-colors">
                                                            <Briefcase className="h-4 w-4 text-slate-600" />
                                                        </div>
                                                        <span className="font-medium text-slate-700 text-sm">Create Project</span>
                                                    </div>
                                                    <ChevronRight className="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                                </button>
                                                <button onClick={() => setCurrentSection('tasks')} className="w-full flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm transition-all text-left group">
                                                    <div className="flex items-center gap-3">
                                                        <div className="bg-slate-50 p-2 rounded-lg group-hover:bg-white transition-colors">
                                                            <CheckSquare className="h-4 w-4 text-slate-600" />
                                                        </div>
                                                        <span className="font-medium text-slate-700 text-sm">Assign Task</span>
                                                    </div>
                                                    <ChevronRight className="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                                </button>
                                            </div>
                                        </div>

                                        {/* Pending Tasks */}
                                        <div>
                                            <div className="flex items-center justify-between mb-4">
                                                <h3 className="text-sm font-semibold text-slate-900">Pending Tasks</h3>
                                                <button onClick={() => setCurrentSection('tasks')} className="text-sm text-slate-500 hover:text-slate-900 transition-colors">View all</button>
                                            </div>
                                            <div className="bg-white border border-slate-100 rounded-xl shadow-sm p-4 space-y-4">
                                                {tasks.filter(t => t.category !== 'Done').slice(0, 4).map((t) => (
                                                    <div key={t.id} className="flex items-start gap-3">
                                                        <div className="mt-0.5">
                                                            <div className="w-4 h-4 rounded border border-slate-300 flex items-center justify-center text-transparent hover:border-slate-400 cursor-pointer">
                                                                <Check className="h-3 w-3" />
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <p className="text-sm text-slate-700 font-medium leading-tight">{t.title}</p>
                                                            <p className="text-xs text-slate-500 mt-1">Due {formatDate(t.due)}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Recent Activity */}
                                        <div>
                                            <h3 className="text-sm font-semibold text-slate-900 mb-4">Recent Activity</h3>
                                            <div className="bg-white border border-slate-100 rounded-xl shadow-sm p-5">
                                                <ActivityTimeline items={activityLogs.slice(0, 3)} />
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        )}

                        {/* 2. CLIENTS DIRECTORY */}
                        {currentSection === 'clients' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Clients" 
                                    subtitle="Manage your clients, contacts, and their billing profiles."
                                    actions={
                                        <Button size="sm" onClick={() => setShowAddClientModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Client
                                        </Button>
                                    }
                                />

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Client / Company</th>
                                                    <th className="px-6 py-3.5">Contact Detail</th>
                                                    <th className="px-6 py-3.5">Address</th>
                                                    <th className="px-6 py-3.5 text-right">Invoiced</th>
                                                    <th className="px-6 py-3.5 text-right">Paid</th>
                                                    <th className="px-6 py-3.5 text-center">Wallet Action</th>
                                                    <th className="px-6 py-3.5 text-right">Control</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {activeClients.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={7} className="p-0">
                                                            <EmptyState 
                                                                icon={Users} 
                                                                title="No Clients" 
                                                                description="Establish client entities to start generating invoices and tracking project schedules."
                                                            />
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    activeClients.map((client) => (
                                                        <tr key={client.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                            <td className="px-6 py-4">
                                                                <span className="font-semibold text-slate-900 block">{client.name}</span>
                                                                <span className="text-slate-400 text-xs mt-0.5 block">{client.company}</span>
                                                            </td>
                                                            <td className="px-6 py-4 font-mono text-xs">
                                                                <span className="block text-slate-600">{client.email}</span>
                                                                <span className="block text-slate-400 mt-0.5">{client.phone}</span>
                                                            </td>
                                                            <td className="px-6 py-4 text-slate-500 text-xs truncate max-w-[150px]">{client.address}</td>
                                                            <td className="px-6 py-4 text-right font-semibold text-slate-950 font-mono">
                                                                <CurrencyDisplay amount={client.totalInvoiced} currency={client.currency || 'USD'} />
                                                            </td>
                                                            <td className="px-6 py-4 text-right font-bold text-emerald-600 font-mono">
                                                                <CurrencyDisplay amount={client.totalPaid} currency={client.currency || 'USD'} />
                                                            </td>
                                                            <td className="px-6 py-4 text-center">
                                                                <Button 
                                                                    size="sm" 
                                                                    variant="outline" 
                                                                    className="h-8 shadow-none border-indigo-100 text-indigo-600 hover:bg-indigo-50 font-semibold"
                                                                    onClick={() => {
                                                                        setSelectedClient(client);
                                                                        setShowWalletModal(true);
                                                                    }}
                                                                >
                                                                    <CreditCard className="mr-1 h-3.5 w-3.5" /> Adjust Balance
                                                                </Button>
                                                            </td>
                                                            <td className="px-6 py-4 text-right">
                                                                <div className="flex items-center justify-end gap-1.5">
                                                                    <button 
                                                                        onClick={() => {
                                                                            setSelectedClient(client);
                                                                            setClientForm({
                                                                                name: client.name,
                                                                                email: client.email || '',
                                                                                phone: client.phone || '',
                                                                                address: client.address || '',
                                                                                currency: client.currency || 'USD'
                                                                            });
                                                                            setShowEditClientModal(true);
                                                                        }} 
                                                                        className="p-1 hover:bg-slate-100 rounded text-slate-500"
                                                                    >
                                                                        <Edit2 className="h-3.5 w-3.5" />
                                                                    </button>
                                                                    <button 
                                                                        onClick={() => handleDeleteClient(client)} 
                                                                        className="p-1 hover:bg-rose-50 rounded text-rose-500"
                                                                    >
                                                                        <Trash2 className="h-3.5 w-3.5" />
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 3. PROJECTS & MILESTONES */}
                        {currentSection === 'projects' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Projects" 
                                    subtitle="Manage active projects, track progress, and monitor deadlines."
                                    actions={
                                        <Button size="sm" onClick={() => setShowAddProjectModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> New Project
                                        </Button>
                                    }
                                />

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {projects.map((proj) => (
                                        <SectionCard key={proj.id}>
                                            <div className="p-5 space-y-4">
                                                <div className="flex items-start justify-between">
                                                    <div>
                                                        <h3 className="font-semibold text-slate-800 text-[14px]">{proj.name}</h3>
                                                        <span className="text-xs text-slate-400 mt-1 block">{proj.client}</span>
                                                    </div>
                                                    <Badge className={`text-[10px] rounded uppercase font-bold tracking-wider ${
                                                        proj.status === 'Completed' ? 'bg-emerald-50 text-emerald-700' :
                                                        proj.status === 'Active' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600'
                                                    }`}>
                                                        {proj.status}
                                                    </Badge>
                                                </div>

                                                <div className="space-y-1">
                                                    <div className="flex justify-between text-xs font-semibold text-slate-500">
                                                        <span>Progress</span>
                                                        <span>{proj.progress}%</span>
                                                    </div>
                                                    <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                                        <div className="bg-indigo-600 h-full rounded-full transition-all" style={{ width: `${proj.progress}%` }} />
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between text-xs pt-3 border-t border-slate-100 text-slate-400 font-mono">
                                                    <span>Budget: <span className="font-semibold text-slate-700">{formatMoney(proj.budget, 'USD')}</span></span>
                                                    <span>Deadline: <span className="font-semibold text-slate-700">{formatDate(proj.deadline)}</span></span>
                                                </div>
                                            </div>
                                        </SectionCard>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* 4. TASK MANAGEMENT (KANBAN) */}
                        {currentSection === 'tasks' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Tasks" 
                                    subtitle="Manage and organize your team's tasks and priorities."
                                />

                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                                    {['Todo', 'In Progress', 'In Review', 'Done'].map((lane) => {
                                        const laneTasks = tasks.filter(t => t.category === lane);
                                        return (
                                            <div key={lane} className="bg-slate-50/80 border border-slate-200/60 p-3.5 rounded-xl space-y-4">
                                                <div className="flex items-center justify-between pb-1">
                                                    <h3 className="font-semibold text-slate-700 text-xs uppercase tracking-wider">{lane}</h3>
                                                    <Badge className="bg-slate-200 text-slate-700 hover:bg-slate-200 rounded-full text-[10px] font-bold">
                                                        {laneTasks.length}
                                                    </Badge>
                                                </div>

                                                {/* Task Cards */}
                                                <div className="space-y-2">
                                                    {laneTasks.map((t) => (
                                                        <div key={t.id} className="bg-white border border-slate-200/80 p-3 rounded-lg shadow-sm hover:border-slate-300 transition space-y-3">
                                                            <p className="text-[13px] font-medium text-slate-800 leading-tight">{t.title}</p>
                                                            <div className="flex items-center justify-between text-[11px] text-slate-400 font-medium">
                                                                <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ${
                                                                    t.priority === 'Urgent' ? 'bg-rose-50 text-rose-700 border border-rose-100' :
                                                                    t.priority === 'High' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'
                                                                }`}>
                                                                    {t.priority}
                                                                </span>
                                                                <span className="font-mono">{t.due}</span>
                                                            </div>
                                                            <div className="flex items-center justify-between pt-2.5 border-t border-slate-100">
                                                                <div className="flex items-center gap-1.5">
                                                                    <div className="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-[9px]">
                                                                        {t.assignee.substring(0, 2)}
                                                                    </div>
                                                                    <span className="text-[11px] text-slate-500 font-semibold">{t.assignee}</span>
                                                                </div>
                                                                <div className="flex items-center gap-1 shrink-0">
                                                                    <button onClick={() => moveTask(t.id, 'backward')} className="p-0.5 hover:bg-slate-100 rounded text-slate-400">
                                                                        ←
                                                                    </button>
                                                                    <button onClick={() => moveTask(t.id, 'forward')} className="p-0.5 hover:bg-slate-100 rounded text-slate-400">
                                                                        →
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>

                                                {/* Lane Quick Input */}
                                                <div className="pt-2">
                                                    <div className="flex gap-1.5">
                                                        <Input 
                                                            placeholder="Add task..." 
                                                            className="h-8 text-xs shadow-none"
                                                            value={quickTaskTitle}
                                                            onChange={(e) => setQuickTaskTitle(e.target.value)}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') handleQuickAddTask(lane);
                                                            }}
                                                        />
                                                        <Button size="icon" className="h-8 w-8 shadow-none" onClick={() => handleQuickAddTask(lane)}>
                                                            <Plus className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* 5. INVOICES & BILLING */}
                        {currentSection === 'invoices' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Invoices" 
                                    subtitle="Create, send, and track client invoices."
                                    actions={
                                        <Link 
                                            href={route('erp.invoices.create')}
                                            className={cn(buttonVariants({ size: 'sm' }), "shadow-none")}
                                        >
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> New Invoice
                                        </Link>
                                    }
                                />

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Invoice Code</th>
                                                    <th className="px-6 py-3.5">Client Tenant</th>
                                                    <th className="px-6 py-3.5">Date Issued</th>
                                                    <th className="px-6 py-3.5">Date Due</th>
                                                    <th className="px-6 py-3.5 text-right">Invoice Sum</th>
                                                    <th className="px-6 py-3.5 text-center">Status</th>
                                                    <th className="px-6 py-3.5 text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {activeInvoices.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={7} className="p-0">
                                                            <EmptyState 
                                                                icon={FileText} 
                                                                title="No Invoices" 
                                                                description="Establish your first invoice record to start tracking claims."
                                                            />
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    activeInvoices.map((inv) => (
                                                        <tr key={inv.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                            <td className="px-6 py-4">
                                                                <Link href={route('erp.invoices.show', inv.id)} className="font-mono font-bold text-indigo-600 hover:underline">
                                                                    {inv.invoiceNumber}
                                                                </Link>
                                                            </td>
                                                            <td className="px-6 py-4 font-semibold text-slate-900">{inv.clientName}</td>
                                                            <td className="px-6 py-4 text-slate-400 font-mono text-xs">{inv.issuedDate ? formatDate(inv.issuedDate) : '-'}</td>
                                                            <td className="px-6 py-4 text-slate-400 font-mono text-xs">{inv.dueDate ? formatDate(inv.dueDate) : '-'}</td>
                                                            <td className="px-6 py-4 text-right font-bold text-slate-950 font-mono">
                                                                <CurrencyDisplay amount={inv.amount} currency={inv.currency} />
                                                            </td>
                                                            <td className="px-6 py-4 text-center">
                                                                <Badge className={`text-[10px] uppercase font-bold tracking-wider rounded ${
                                                                    inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-none' :
                                                                    inv.status === 'sent' ? 'bg-amber-50 text-amber-700 border-none' : 'bg-slate-100 text-slate-600'
                                                                }`}>
                                                                    {inv.status}
                                                                </Badge>
                                                            </td>
                                                            <td className="px-6 py-4 text-right">
                                                                 <div className="flex items-center justify-end gap-1">
                                                                     <Link 
                                                                         href={route('erp.invoices.show', inv.id)} 
                                                                         className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), "h-8 w-8 text-slate-400 hover:text-slate-900")}
                                                                     >
                                                                         <Eye className="h-4 w-4" />
                                                                     </Link>
                                                                     <Link 
                                                                         href={route('erp.invoices.edit', inv.id)} 
                                                                         className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), "h-8 w-8 text-slate-400 hover:text-slate-900")}
                                                                     >
                                                                         <Edit2 className="h-4 w-4" />
                                                                     </Link>
                                                                 </div>
                                                             </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 6. EXPENSE MANAGEMENT */}
                        {currentSection === 'expenses' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Expenses" 
                                    subtitle="Track and log operational expenses."
                                    actions={
                                        <Button size="sm" onClick={() => setShowAddExpenseModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Log Expense
                                        </Button>
                                    }
                                />

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Expense Title</th>
                                                    <th className="px-6 py-3.5">Category</th>
                                                    <th className="px-6 py-3.5">Date logged</th>
                                                    <th className="px-6 py-3.5 text-right">Amount</th>
                                                    <th className="px-6 py-3.5 text-center">Status</th>
                                                    <th className="px-6 py-3.5 text-right">Document</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {expenses.map((exp) => (
                                                    <tr key={exp.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                        <td className="px-6 py-4 font-semibold text-slate-900">{exp.title}</td>
                                                        <td className="px-6 py-4 text-slate-500 font-medium">{exp.category}</td>
                                                        <td className="px-6 py-4 text-slate-400 font-mono text-xs">{formatDate(exp.date)}</td>
                                                        <td className="px-6 py-4 text-right font-bold text-slate-950 font-mono">
                                                            {formatMoney(exp.amount, 'USD')}
                                                        </td>
                                                        <td className="px-6 py-4 text-center">
                                                            <Badge className={`text-[10px] uppercase font-bold tracking-wider rounded ${
                                                                exp.status === 'Paid' || exp.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
                                                            }`}>
                                                                {exp.status}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-6 py-4 text-right text-indigo-600 font-medium hover:underline cursor-pointer font-mono text-xs">
                                                            {exp.receipt}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 7. DOCUMENT VAULT */}
                        {currentSection === 'documents' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Files" 
                                    subtitle="Secure cloud repository for your documents and files."
                                    actions={
                                        <Button size="sm" onClick={() => setShowAddDocModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Upload File
                                        </Button>
                                    }
                                />

                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    {['Contracts', 'Receipts', 'Proposals', 'Tax Forms'].map((fol) => (
                                        <SectionCard key={fol} className="hover:border-slate-300 transition cursor-pointer">
                                            <div className="p-4 flex items-center gap-3">
                                                <Folder className="h-8 w-8 text-indigo-500 shrink-0" />
                                                <div>
                                                    <span className="font-semibold text-slate-800 text-xs block">{fol}</span>
                                                    <span className="text-[10px] text-slate-400">
                                                        {fol === 'Contracts' ? contracts.length : fol === 'Receipts' ? expenses.length : 4} files
                                                    </span>
                                                </div>
                                            </div>
                                        </SectionCard>
                                    ))}
                                </div>

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Filename</th>
                                                    <th className="px-6 py-3.5">Storage Provider</th>
                                                    <th className="px-6 py-3.5">Tags</th>
                                                    <th className="px-6 py-3.5">File size</th>
                                                    <th className="px-6 py-3.5 text-right">Access</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {documents.map((doc) => (
                                                    <tr key={doc.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                        <td className="px-6 py-4 flex items-center gap-3">
                                                            <div className="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                                                <FileText className="h-4 w-4 text-slate-400" />
                                                            </div>
                                                            <div>
                                                                <span className="font-semibold text-slate-900 block">{doc.name}</span>
                                                                <span className="text-[11px] text-slate-400">Uploaded by {doc.uploadedBy} • {formatDate(doc.date)}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <div className="flex items-center gap-1.5">
                                                                <Cloud className="h-3.5 w-3.5 text-indigo-500" />
                                                                <span className="text-slate-600 font-medium">{doc.provider}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <div className="flex gap-1.5 flex-wrap">
                                                                {doc.tags?.map((t: string) => (
                                                                    <Badge key={t} variant="outline" className="text-[10px] font-medium text-slate-500 bg-white shadow-none rounded-md">
                                                                        {t}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 font-mono text-xs text-slate-400">{doc.size}</td>
                                                        <td className="px-6 py-4 text-right">
                                                            <div className="flex items-center justify-end gap-2 text-slate-400">
                                                                <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-indigo-600"><LinkIcon className="h-4 w-4" /></Button>
                                                                <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-indigo-600"><Eye className="h-4 w-4" /></Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 8. LEGAL AGREEMENTS */}
                        {currentSection === 'contracts' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Contracts" 
                                    subtitle="Draft and track client service agreements and contracts."
                                    actions={
                                        <Button size="sm" onClick={() => setShowAddContractModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Draft Contract
                                        </Button>
                                    }
                                />

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Agreement Sheet</th>
                                                    <th className="px-6 py-3.5">Client Tenant</th>
                                                    <th className="px-6 py-3.5 text-right">Value</th>
                                                    <th className="px-6 py-3.5 text-center">Status</th>
                                                    <th className="px-6 py-3.5 text-right">Date Created</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {contracts.map((cont) => (
                                                    <tr key={cont.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                        <td className="px-6 py-4 flex items-center gap-2">
                                                            <FileLock className="h-4 w-4 text-indigo-500 shrink-0" />
                                                            <span className="font-semibold text-slate-900">{cont.title}</span>
                                                        </td>
                                                        <td className="px-6 py-4 font-medium text-slate-800">{cont.client}</td>
                                                        <td className="px-6 py-4 text-right font-bold text-slate-950 font-mono">
                                                            {cont.value > 0 ? formatMoney(cont.value, 'USD') : 'N/A'}
                                                        </td>
                                                        <td className="px-6 py-4 text-center">
                                                            <Badge className={`text-[10px] uppercase font-bold tracking-wider rounded ${
                                                                cont.status === 'Signed' ? 'bg-emerald-50 text-emerald-700' :
                                                                cont.status === 'Sent to Client' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'
                                                            }`}>
                                                                {cont.status}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-6 py-4 text-right text-slate-400 font-mono text-xs">{formatDate(cont.date)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 9. WALLET LEDGER */}
                        {currentSection === 'transactions' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Transactions" 
                                    subtitle="View all wallet activity, payments, and balance adjustments."
                                />

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Reference ID</th>
                                                    <th className="px-6 py-3.5">Transaction Type</th>
                                                    <th className="px-6 py-3.5">Direction</th>
                                                    <th className="px-6 py-3.5 text-right">Sum</th>
                                                    <th className="px-6 py-3.5 text-right">Authorized</th>
                                                    <th className="px-6 py-3.5 text-right">Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100 font-sans text-[13px] text-slate-700">
                                                {/* Prepopulated with realistic corporate transaction models */}
                                                <tr className="hover:bg-slate-50 transition">
                                                    <td className="px-6 py-4 font-mono font-bold text-indigo-600">#TXN-901</td>
                                                    <td className="px-6 py-4">
                                                        <span className="font-semibold text-slate-900 block">Invoice Payment Captured</span>
                                                        <span className="text-slate-400 text-xs block">Settled invoice #INV-4929 from wallet balance</span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <Badge className="bg-emerald-50 text-emerald-700 rounded font-bold text-[10px]">CREDIT</Badge>
                                                    </td>
                                                    <td className="px-6 py-4 text-right"><FinancialAmount amount={1500} colorize={true} /></td>
                                                    <td className="px-6 py-4 text-right font-medium text-slate-600">System Core</td>
                                                    <td className="px-6 py-4 text-right text-slate-400 font-mono text-xs">2026-05-17 10:14</td>
                                                </tr>
                                                <tr className="hover:bg-slate-50 transition">
                                                    <td className="px-6 py-4 font-mono font-bold text-indigo-600">#TXN-900</td>
                                                    <td className="px-6 py-4">
                                                        <span className="font-semibold text-slate-900 block">Manual Credit Allocation</span>
                                                        <span className="text-slate-400 text-xs block">Authorized compensation adjustment</span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <Badge className="bg-emerald-50 text-emerald-700 rounded font-bold text-[10px]">CREDIT</Badge>
                                                    </td>
                                                    <td className="px-6 py-4 text-right"><FinancialAmount amount={350} colorize={true} /></td>
                                                    <td className="px-6 py-4 text-right font-medium text-slate-600">Sarah Lin</td>
                                                    <td className="px-6 py-4 text-right text-slate-400 font-mono text-xs">2026-05-16 14:22</td>
                                                </tr>
                                                <tr className="hover:bg-slate-50 transition">
                                                    <td className="px-6 py-4 font-mono font-bold text-indigo-600">#TXN-899</td>
                                                    <td className="px-6 py-4">
                                                        <span className="font-semibold text-slate-900 block">Withdrawal Settlement</span>
                                                        <span className="text-slate-400 text-xs block">Wired client earnings payout</span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <Badge className="bg-rose-50 text-rose-700 rounded font-bold text-[10px]">DEBIT</Badge>
                                                    </td>
                                                    <td className="px-6 py-4 text-right"><FinancialAmount amount={-4200} colorize={true} /></td>
                                                    <td className="px-6 py-4 text-right font-medium text-slate-600">Jane Doe</td>
                                                    <td className="px-6 py-4 text-right text-slate-400 font-mono text-xs">2026-05-12 09:30</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 10. TEAM ACCESS CONTROL */}
                        {currentSection === 'team' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Team" 
                                    subtitle="Manage your team members and their workspace access."
                                />

                                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                                    <div className="lg:col-span-6 space-y-6">
                                        <SectionCard>
                                            <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                                <h3 className="font-semibold text-slate-800 text-[14px]">Teammates Directory</h3>
                                                <Badge className="bg-indigo-50 text-indigo-700 border-none font-bold text-[10px]">Active Workspace</Badge>
                                            </div>
                                            <div className="divide-y divide-slate-100">
                                                {teamMembers.map((team) => (
                                                    <div key={team.id} className="p-4 hover:bg-slate-50 transition flex items-center justify-between text-[13px]">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                                                {team.name.substring(0, 2).toUpperCase()}
                                                            </div>
                                                            <div>
                                                                <span className="font-semibold text-slate-900 block">{team.name}</span>
                                                                <span className="text-slate-400 text-xs block mt-0.5">{team.email}</span>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-6">
                                                            <Badge className="bg-slate-100 text-slate-700 rounded font-semibold text-[11px] hover:bg-slate-100">
                                                                {team.role}
                                                            </Badge>
                                                            <span className="text-slate-400 text-xs font-mono">{team.activities} actions logged</span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </SectionCard>
                                    </div>

                                    {/* Permissions definitions card */}
                                    <div className="lg:col-span-4 space-y-6">
                                        <SectionCard>
                                            <div className="p-5 space-y-4">
                                                <h4 className="font-semibold text-slate-800 text-[13px] flex items-center gap-1.5">
                                                    <Sliders className="h-4 w-4 text-indigo-600" />
                                                    Granular Role Matrix
                                                </h4>
                                                <p className="text-xs text-slate-500 leading-relaxed">
                                                    Configure read/write gates across the modules. Owner maintains central ledger access.
                                                </p>
                                                
                                                <div className="space-y-3 pt-3 border-t border-slate-100 text-xs text-slate-700">
                                                    <div className="flex justify-between font-semibold border-b pb-1">
                                                        <span>Module Scope</span>
                                                        <span>Min. Required Role</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span>Central Ledger</span>
                                                        <Badge variant="outline" className="text-[10px] font-bold">Owner</Badge>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span>Invoice Generation</span>
                                                        <Badge variant="outline" className="text-[10px] font-bold">Accountant</Badge>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span>Task Assignments</span>
                                                        <Badge variant="outline" className="text-[10px] font-bold">Staff</Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        </SectionCard>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* 11. UNIVERSAL NOTES SYSTEM */}
                        {currentSection === 'notes' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Notes" 
                                    subtitle="Workspace scratchpad for logging internal memos and pinned notes."
                                    actions={
                                        <Button size="sm" onClick={handleCreateNote} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Note
                                        </Button>
                                    }
                                />

                                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                                    {/* Left Notes List */}
                                    <div className="lg:col-span-4 space-y-3">
                                        {notes.map((n) => (
                                            <div 
                                                key={n.id} 
                                                onClick={() => handleSelectNote(n)}
                                                className={`p-4 rounded-xl border transition cursor-pointer text-left ${
                                                    selectedNote?.id === n.id 
                                                    ? 'bg-slate-900 border-slate-950 text-white' 
                                                    : 'bg-white border-slate-200 hover:border-slate-300'
                                                }`}
                                            >
                                                <div className="flex items-start justify-between">
                                                    <h4 className="font-semibold text-xs truncate max-w-[150px]">{n.title}</h4>
                                                    <button 
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            togglePinNote(n.id);
                                                        }}
                                                        className={`p-0.5 rounded ${selectedNote?.id === n.id ? 'text-white' : 'text-slate-400 hover:text-slate-600'}`}
                                                    >
                                                        <Pin className={`h-3 w-3 ${n.pinned ? 'fill-current' : ''}`} />
                                                    </button>
                                                </div>
                                                <p className={`text-[11px] truncate mt-1.5 ${
                                                    selectedNote?.id === n.id ? 'text-slate-300' : 'text-slate-500'
                                                }`}>{n.content}</p>
                                                <div className="flex items-center justify-between mt-3 text-[10px] font-mono">
                                                    <span className={`px-1.5 py-0.5 rounded ${
                                                        selectedNote?.id === n.id ? 'bg-slate-800 text-slate-200' : 'bg-slate-100 text-slate-600'
                                                    }`}>{n.category}</span>
                                                    <span className={selectedNote?.id === n.id ? 'text-slate-400' : 'text-slate-400'}>{n.date}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Right Minimal Note Editor */}
                                    <div className="lg:col-span-6">
                                        {selectedNote ? (
                                            <SectionCard className="p-6 space-y-4">
                                                <div className="flex items-center justify-between border-b pb-4">
                                                    <div className="flex items-center gap-3 w-full">
                                                        <select 
                                                            className="flex h-8 w-[100px] rounded-md border border-input bg-background px-2 py-1 text-xs shadow-none outline-none focus:ring-1 focus:ring-slate-950"
                                                            value={noteEditor.category}
                                                            onChange={(e) => setNoteEditor(prev => ({ ...prev, category: e.target.value }))}
                                                        >
                                                            <option value="Internal">Internal</option>
                                                            <option value="Client">Client</option>
                                                            <option value="Project">Project</option>
                                                        </select>
                                                        <Input 
                                                            className="border-none font-semibold text-[15px] h-9 focus-visible:ring-0 px-0 shadow-none"
                                                            placeholder="Note Title"
                                                            value={noteEditor.title}
                                                            onChange={(e) => setNoteEditor(prev => ({ ...prev, title: e.target.value }))}
                                                        />
                                                    </div>
                                                    <Badge className="bg-indigo-50 text-indigo-700 hover:bg-indigo-50 border-none font-bold text-[10px] shrink-0 font-mono">
                                                        Committed
                                                    </Badge>
                                                </div>

                                                <Textarea 
                                                    className="border-none font-sans text-[13px] min-h-[250px] focus-visible:ring-0 p-0 shadow-none leading-relaxed text-slate-600"
                                                    placeholder="Start typing your note here..."
                                                    value={noteEditor.content}
                                                    onChange={(e) => setNoteEditor(prev => ({ ...prev, content: e.target.value }))}
                                                />
                                            </SectionCard>
                                        ) : (
                                            <div className="h-full bg-slate-50 border border-slate-200 border-dashed rounded-xl flex flex-col items-center justify-center p-8 text-center text-slate-400">
                                                <Pin className="h-8 w-8 mb-2" />
                                                <p className="text-xs">Select a note from the left to start editing.</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* 12. CALENDAR (WORKSPACE SCHEDULE) */}
                        {currentSection === 'calendar' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Calendar" 
                                    subtitle="View task due dates, contract timelines, and upcoming events."
                                />

                                <SectionCard>
                                    <div className="p-6">
                                        <div className="flex items-center justify-between mb-4">
                                            <h3 className="font-semibold text-slate-800 text-[14px]">May 2026</h3>
                                            <div className="flex items-center gap-1.5">
                                                <Button size="icon" variant="outline" className="h-8 w-8 shadow-none">←</Button>
                                                <Button size="icon" variant="outline" className="h-8 w-8 shadow-none">→</Button>
                                            </div>
                                        </div>

                                        {/* Simple beautiful HSL calendar grid */}
                                        <div className="grid grid-cols-7 gap-px bg-slate-200 rounded-lg overflow-hidden border border-slate-200">
                                            {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(d => (
                                                <div key={d} className="bg-slate-50 py-2 text-center text-xs font-semibold text-slate-500">
                                                    {d}
                                                </div>
                                            ))}
                                            {/* Days (1 to 28) */}
                                            {Array.from({ length: 28 }).map((_, idx) => {
                                                const dayNum = idx + 1;
                                                const isToday = dayNum === 18; // May 18 2026
                                                const hasEvent = dayNum === 12 || dayNum === 15 || dayNum === 20 || dayNum === 25;
                                                
                                                return (
                                                    <div key={idx} className="bg-white min-h-[70px] p-2 flex flex-col justify-between hover:bg-slate-50 transition group">
                                                        <span className={`text-xs font-mono font-medium ${
                                                            isToday 
                                                            ? 'bg-indigo-600 text-white w-5 h-5 flex items-center justify-center rounded-full font-bold shadow-md shadow-indigo-100' 
                                                            : 'text-slate-600'
                                                        }`}>
                                                            {dayNum}
                                                        </span>
                                                        
                                                        {hasEvent && (
                                                            <div className="text-[10px] truncate leading-tight font-semibold bg-indigo-50 border-l border-indigo-500 text-indigo-700 px-1.5 py-0.5 rounded mt-1">
                                                                {dayNum === 12 ? 'TXN-899 Wired' :
                                                                 dayNum === 15 ? 'MSA Signed' :
                                                                 dayNum === 20 ? 'Q1 Reconcile' : 'MSA Revisions'}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 13. SUPPORT TICKETS */}
                        {currentSection === 'support' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Support" 
                                    subtitle="Track client helpdesk tickets and resolve inquiries."
                                    actions={
                                        <Button size="sm" onClick={() => setShowAddTicketModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Open Ticket
                                        </Button>
                                    }
                                />

                                <SectionCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Ticket ID</th>
                                                    <th className="px-6 py-3.5">Ticket Subject</th>
                                                    <th className="px-6 py-3.5">Client Tenant</th>
                                                    <th className="px-6 py-3.5">Priority</th>
                                                    <th className="px-6 py-3.5">Status</th>
                                                    <th className="px-6 py-3.5 text-right">Date Created</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100 font-sans text-[13px] text-slate-700">
                                                {supportTickets.map((t) => (
                                                    <tr key={t.id} className="hover:bg-slate-50 transition">
                                                        <td className="px-6 py-4 font-mono font-bold text-indigo-600">#TCK-{t.id}</td>
                                                        <td className="px-6 py-4 font-semibold text-slate-900">{t.title}</td>
                                                        <td className="px-6 py-4 font-medium text-slate-800">{t.client}</td>
                                                        <td className="px-6 py-4">
                                                            <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ${
                                                                t.priority === 'High' ? 'bg-rose-50 text-rose-700' :
                                                                t.priority === 'Medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'
                                                            }`}>
                                                                {t.priority}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <Badge className={`text-[10px] uppercase font-bold tracking-wider rounded ${
                                                                t.status === 'Resolved' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'
                                                            }`}>
                                                                {t.status}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-6 py-4 text-right text-slate-400 font-mono text-xs">{formatDate(t.date)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                        {/* 14. AUDIT TIMELINE */}
                        {currentSection === 'activity' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Activity" 
                                    subtitle="Log of actions taken within the workspace."
                                />

                                <SectionCard className="p-6">
                                    <ActivityTimeline items={activityLogs} />
                                </SectionCard>
                            </div>
                        )}

                        {/* 15. WORKSPACE SETTINGS */}
                        {currentSection === 'settings' && (
                            <div className="space-y-6">
                                <PageHeader 
                                    title="Settings" 
                                    subtitle="Manage your workspace preferences, billing parameters, and brand identity."
                                />

                                <SectionCard>
                                    <div className="p-6 space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="space-y-2">
                                                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Workspace Brand Name</label>
                                                <Input 
                                                    value={settingsForm.workspaceName}
                                                    className="shadow-none focus:ring-slate-900 border-slate-200"
                                                    onChange={(e) => setSettingsForm(prev => ({ ...prev, workspaceName: e.target.value }))}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Business Base Currency</label>
                                                <select 
                                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                                    value={settingsForm.defaultCurrency}
                                                    onChange={(e) => setSettingsForm(prev => ({ ...prev, defaultCurrency: e.target.value }))}
                                                >
                                                    <option value="USD">USD ($)</option>
                                                    <option value="EUR">EUR (€)</option>
                                                    <option value="EGP">EGP (EGP)</option>
                                                </select>
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Standard VAT / Tax Rate (%)</label>
                                                <Input 
                                                    value={settingsForm.taxRate}
                                                    className="shadow-none focus:ring-slate-900 border-slate-200 font-mono"
                                                    onChange={(e) => setSettingsForm(prev => ({ ...prev, taxRate: e.target.value }))}
                                                />
                                            </div>
                                        </div>

                                        <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
                                            <div className="flex gap-2 text-slate-500 text-xs font-medium">
                                                <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                                Workspace settings live-audited under PCI protocols.
                                            </div>
                                            <Button size="sm" className="shadow-none" onClick={() => {
                                                prependActivity('Settings Updated', `Modified workspace branded title and VAT ratios to ${settingsForm.taxRate}%`);
                                                toast({ description: 'Workspace parameters successfully configured.' });
                                            }}>
                                                Save Settings
                                            </Button>
                                        </div>
                                    </div>
                                </SectionCard>

                                <SectionCard>
                                    <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                                        <div>
                                            <h3 className="font-semibold text-slate-900 text-sm">Storage Integrations</h3>
                                            <p className="text-xs text-slate-500 mt-1">Connect your workspace directly to external cloud storage (S3 compatible).</p>
                                        </div>
                                        <Button size="sm" variant="outline" className="shadow-sm" onClick={() => setShowAddProviderModal(true)}>
                                            <Plus className="h-3.5 w-3.5 mr-1.5" /> Add Provider
                                        </Button>
                                    </div>
                                    <div className="divide-y divide-slate-100">
                                        {storageProviders.map(provider => (
                                            <div key={provider.id} className="p-5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                                <div className="flex items-center gap-4">
                                                    <div className="h-10 w-10 bg-white border border-slate-200 rounded-lg shadow-sm flex items-center justify-center shrink-0">
                                                        <Database className="h-5 w-5 text-indigo-600" />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold text-slate-900 text-sm">{provider.name}</span>
                                                            {provider.isDefault && <Badge variant="secondary" className="text-[9px] uppercase tracking-wider bg-indigo-50 text-indigo-700">Default</Badge>}
                                                        </div>
                                                        <span className="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
                                                            <HardDrive className="h-3 w-3" /> {provider.bucket} ({provider.driver})
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <Badge variant="outline" className="text-[10px] text-emerald-600 border-emerald-200 bg-emerald-50"><CheckCircle className="h-3 w-3 mr-1" /> {provider.status}</Badge>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-400 hover:text-rose-600">
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </SectionCard>
                            </div>
                        )}

                    </div>
                </div>

            </div>

            {/* ────────────────────────────────────────────────────────
                MODALS AND OVERLAYS SECTION
                ──────────────────────────────────────────────────────── */}
            
            {/* ADD CLIENT MODAL */}
            {showAddClientModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                    <SectionCard className="w-full max-w-md shadow-2xl animate-scale-up">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Establish Client Entity</h3>
                            <button onClick={() => setShowAddClientModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddClient} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client Name</label>
                                <Input required placeholder="Jane Doe" value={clientForm.name} onChange={e => setClientForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Email Address</label>
                                <Input required type="email" placeholder="jane@globex.org" value={clientForm.email} onChange={e => setClientForm(prev => ({ ...prev, email: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Company Name</label>
                                <Input placeholder="Globex Corp" value={clientForm.phone} onChange={e => setClientForm(prev => ({ ...prev, phone: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Physical Address</label>
                                <Input placeholder="120 San Francisco, CA" value={clientForm.address} onChange={e => setClientForm(prev => ({ ...prev, address: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Wallet Currency</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={clientForm.currency}
                                    onChange={e => setClientForm(prev => ({ ...prev, currency: e.target.value }))}
                                >
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="EGP">EGP (EGP)</option>
                                </select>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddClientModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Commit Record</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* EDIT CLIENT MODAL */}
            {showEditClientModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Edit Client Record</h3>
                            <button onClick={() => { setShowEditClientModal(false); setSelectedClient(null); }} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleEditClient} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client Name</label>
                                <Input required value={clientForm.name} onChange={e => setClientForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Email Address</label>
                                <Input required type="email" value={clientForm.email} onChange={e => setClientForm(prev => ({ ...prev, email: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Company Name</label>
                                <Input value={clientForm.phone} onChange={e => setClientForm(prev => ({ ...prev, phone: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Physical Address</label>
                                <Input value={clientForm.address} onChange={e => setClientForm(prev => ({ ...prev, address: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Wallet Currency</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={clientForm.currency}
                                    onChange={e => setClientForm(prev => ({ ...prev, currency: e.target.value }))}
                                >
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="EGP">EGP (EGP)</option>
                                </select>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => { setShowEditClientModal(false); setSelectedClient(null); }}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Update Record</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* ADJUST CLIENT WALLET MODAL */}
            {showWalletModal && selectedClient && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                    <SectionCard className="w-full max-w-md shadow-2xl animate-scale-up">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 className="font-semibold text-slate-800 text-[14px]">Adjust Wallet Balance</h3>
                                <p className="text-xs text-slate-400">Client: {selectedClient.name}</p>
                            </div>
                            <button onClick={() => setShowWalletModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleWalletAdjustment} className="p-6 space-y-4">
                            <div className="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center font-mono">
                                <span className="text-xs text-slate-400 block font-sans">Current balance</span>
                                <span className="text-lg font-bold text-slate-800">
                                    {selectedClient.wallet?.balance !== undefined ? selectedClient.wallet.balance : '$0.00'}
                                </span>
                            </div>

                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Adjustment Type</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={walletForm.type}
                                    onChange={e => setWalletForm(prev => ({ ...prev, type: e.target.value }))}
                                >
                                    <option value="credit">Manual Credit Add (+)</option>
                                    <option value="debit">Manual Debit Subtract (-)</option>
                                    <option value="lock">Lock Funds</option>
                                    <option value="unlock">Unlock Funds</option>
                                </select>
                            </div>

                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount ($)</label>
                                <Input required type="number" min="0.01" step="0.01" placeholder="50.00" value={walletForm.amount} onChange={e => setWalletForm(prev => ({ ...prev, amount: e.target.value }))} className="shadow-none font-mono" />
                            </div>

                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Internal Audit Note</label>
                                <Textarea required placeholder="Compensation for project delays or wallet deposit verification reference" value={walletForm.note} onChange={e => setWalletForm(prev => ({ ...prev, note: e.target.value }))} className="shadow-none h-16" />
                            </div>

                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowWalletModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Apply Adjustments</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* ADD PROJECT MODAL */}
            {showAddProjectModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Create Project</h3>
                            <button onClick={() => setShowAddProjectModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddProject} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Project Name</label>
                                <Input required placeholder="E-commerce Gateway" value={newProjectForm.name} onChange={e => setNewProjectForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client Tenant</label>
                                <Input required placeholder="Nexus Tech Inc" value={newProjectForm.client} onChange={e => setNewProjectForm(prev => ({ ...prev, client: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Budget ($)</label>
                                <Input required type="number" placeholder="5000" value={newProjectForm.budget} onChange={e => setNewProjectForm(prev => ({ ...prev, budget: e.target.value }))} className="shadow-none font-mono" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Deadline</label>
                                <Input required type="date" value={newProjectForm.deadline} onChange={e => setNewProjectForm(prev => ({ ...prev, deadline: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Project Leader</label>
                                <Input placeholder="Sarah Lin" value={newProjectForm.leader} onChange={e => setNewProjectForm(prev => ({ ...prev, leader: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddProjectModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Initiate Project</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* LOG EXPENSE MODAL */}
            {showAddExpenseModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Log Corporate Expense</h3>
                            <button onClick={() => setShowAddExpenseModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddExpense} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Expense Title</label>
                                <Input required placeholder="Figma Enterprise seats" value={expenseForm.title} onChange={e => setExpenseForm(prev => ({ ...prev, title: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Category</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={expenseForm.category}
                                    onChange={e => setExpenseForm(prev => ({ ...prev, category: e.target.value }))}
                                >
                                    <option value="Software">Software Scope</option>
                                    <option value="Travel">Travel & Lodging</option>
                                    <option value="Meals">Meals & Client Onboarding</option>
                                    <option value="Hardware">Hardware & Equipment</option>
                                    <option value="Other">Other Overheads</option>
                                </select>
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount ($)</label>
                                <Input required type="number" placeholder="45.00" value={expenseForm.amount} onChange={e => setExpenseForm(prev => ({ ...prev, amount: e.target.value }))} className="shadow-none font-mono" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date Committed</label>
                                <Input type="date" value={expenseForm.date} onChange={e => setExpenseForm(prev => ({ ...prev, date: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddExpenseModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save Expense</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* ADD DOCUMENT VAULT FILE MODAL */}
            {showAddDocModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 className="font-semibold text-slate-800 text-[14px]">Direct Cloud Upload</h3>
                                <p className="text-[10px] text-slate-500 font-mono mt-0.5">Secure transmission to external bucket</p>
                            </div>
                            <button onClick={() => setShowAddDocModal(false)} className="p-1 hover:bg-slate-200 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddDoc} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Document Label</label>
                                <Input required placeholder="Globex_Master_SOW" value={docForm.name} onChange={e => setDocForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Classification</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-none"
                                    value={docForm.type}
                                    onChange={e => setDocForm(prev => ({ ...prev, type: e.target.value }))}
                                >
                                    <option value="Contract">Master Agreement</option>
                                    <option value="Proposal">Client Proposal</option>
                                    <option value="Receipt">Expense Receipt</option>
                                    <option value="Tax">Corporate Tax Sheet</option>
                                </select>
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5"><Cloud className="h-3 w-3" /> Storage Provider</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-none font-medium"
                                    value={docForm.provider}
                                    onChange={e => setDocForm(prev => ({ ...prev, provider: e.target.value }))}
                                >
                                    {storageProviders.map(p => (
                                        <option key={p.id} value={p.name}>{p.name} ({p.driver})</option>
                                    ))}
                                </select>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddDocModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"><Cloud className="h-4 w-4 mr-1.5" /> Upload to Bucket</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* ADD STORAGE PROVIDER MODAL */}
            {showAddProviderModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 className="font-semibold text-slate-800 text-[14px]">Connect Cloud Storage</h3>
                                <p className="text-[10px] text-slate-500 font-mono mt-0.5">S3-Compatible Integration</p>
                            </div>
                            <button onClick={() => setShowAddProviderModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddProvider} className="p-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1 col-span-2">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Integration Name</label>
                                    <Input required placeholder="e.g. AWS S3 Frankfurt" value={providerForm.name} onChange={e => setProviderForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Driver</label>
                                    <select 
                                        className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                        value={providerForm.driver}
                                        onChange={e => setProviderForm(prev => ({ ...prev, driver: e.target.value }))}
                                    >
                                        <option value="s3">AWS S3 (Native)</option>
                                        <option value="s3-cloudflare">Cloudflare R2</option>
                                        <option value="s3-digitalocean">DigitalOcean Spaces</option>
                                        <option value="s3-wasabi">Wasabi</option>
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Region</label>
                                    <Input placeholder="eu-central-1" value={providerForm.region} onChange={e => setProviderForm(prev => ({ ...prev, region: e.target.value }))} className="shadow-none font-mono" />
                                </div>
                                <div className="space-y-1 col-span-2">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Bucket Name</label>
                                    <Input required placeholder="my-erp-files" value={providerForm.bucket} onChange={e => setProviderForm(prev => ({ ...prev, bucket: e.target.value }))} className="shadow-none font-mono" />
                                </div>
                                <div className="space-y-1 col-span-2">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Endpoint URL (Optional)</label>
                                    <Input placeholder="https://<account_id>.r2.cloudflarestorage.com" value={providerForm.endpoint} onChange={e => setProviderForm(prev => ({ ...prev, endpoint: e.target.value }))} className="shadow-none font-mono text-xs" />
                                </div>
                                <div className="space-y-1 col-span-2">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5"><Key className="h-3 w-3" /> Access Credentials</label>
                                    <div className="flex gap-2">
                                        <Input type="password" placeholder="Access Key" required className="shadow-none font-mono text-xs" />
                                        <Input type="password" placeholder="Secret Key" required className="shadow-none font-mono text-xs" />
                                    </div>
                                    <p className="text-[9px] text-slate-400 mt-1">Keys are encrypted at rest using platform APP_KEY.</p>
                                </div>
                            </div>
                            <div className="pt-4 border-t flex justify-between items-center mt-2">
                                <Button type="button" variant="ghost" size="sm" className="text-slate-500">Test Connection</Button>
                                <div className="flex gap-2">
                                    <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddProviderModal(false)}>Cancel</Button>
                                    <Button type="submit" size="sm" className="shadow-none bg-slate-900 text-white font-medium">Connect Bucket</Button>
                                </div>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* DRAFT CONTRACT AGREEMENT MODAL */}
            {showAddContractModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Draft Client Agreement</h3>
                            <button onClick={() => setShowAddContractModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddContract} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Agreement Sheet Title</label>
                                <Input required placeholder="Mutual Non-Disclosure Agreement" value={contractForm.title} onChange={e => setContractForm(prev => ({ ...prev, title: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client Tenant</label>
                                <Input required placeholder="Globex Financials" value={contractForm.client} onChange={e => setContractForm(prev => ({ ...prev, client: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Agreement Value ($)</label>
                                <Input type="number" placeholder="4500" value={contractForm.value} onChange={e => setContractForm(prev => ({ ...prev, value: e.target.value }))} className="shadow-none font-mono" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={contractForm.status}
                                    onChange={e => setContractForm(prev => ({ ...prev, status: e.target.value }))}
                                >
                                    <option value="Draft">Draft Memo</option>
                                    <option value="Sent to Client">Sent to Client</option>
                                    <option value="Signed">Fully Signed</option>
                                </select>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddContractModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Initiate Agreement</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

            {/* OPEN TICKET MODAL */}
            {showAddTicketModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <SectionCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Open Support Ticket</h3>
                            <button onClick={() => setShowAddTicketModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddTicket} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Ticket Subject</label>
                                <Input required placeholder="Invoice transaction double charge error" value={newTicketForm.title} onChange={e => setNewTicketForm(prev => ({ ...prev, title: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client Tenant</label>
                                <Input required placeholder="Nexus Tech Inc" value={newTicketForm.client} onChange={e => setNewTicketForm(prev => ({ ...prev, client: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Priority</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={newTicketForm.priority}
                                    onChange={e => setNewTicketForm(prev => ({ ...prev, priority: e.target.value }))}
                                >
                                    <option value="Low">Low Queue</option>
                                    <option value="Medium">Medium Queue</option>
                                    <option value="High">High Queue</option>
                                </select>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddTicketModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Open Ticket</Button>
                            </div>
                        </form>
                    </SectionCard>
                </div>
            )}

        </AuthenticatedLayout>
    );
}
