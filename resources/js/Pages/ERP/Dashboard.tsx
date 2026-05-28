import ERPLayout from '@/Layouts/ERPLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
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
    Cloud, Database, Link as LinkIcon, HardDrive, Key, CheckCircle, SearchCode, Lock,
    MoreHorizontal, Wallet, RotateCcw, ArrowDownLeft, ArrowUpDown, ArrowDown, ArrowUp
} from 'lucide-react';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { useToast } from '@/Components/ui/use-toast';
import { cn, formatDate, formatMoney } from '@/lib/utils';

import { DataTable } from '@/Components/ui/DataTable';
import { EmptyState } from '@/Components/ui/EmptyState';
import { MetricCard } from '@/Components/ui/MetricCard';
import { ModulePageHeader } from '@/Components/ui/ModulePageHeader';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { ActivityTimeline } from '@/Components/ui/ActivityTimeline';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { StatusBadge } from '@/Components/ui/StatusBadge';
import { useERPMenu } from '@/hooks/useERPMenu';

// FinancialAmount now uses CurrencyDisplay from the component library
export function FinancialAmount({ amount, currency = 'USD', colorize = false }: { amount: number; currency?: string; colorize?: boolean }) {
    const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    let colorClass = 'text-slate-900';
    if (colorize) {
        if (numericAmount > 0) colorClass = 'text-emerald-600';
        else if (numericAmount < 0) colorClass = 'text-rose-600';
    }
    return (
        <span className={`font-mono font-semibold text-[13px] tracking-tight ${colorClass}`}>
            {colorize && numericAmount > 0 ? '+' : ''}
            <CurrencyDisplay amount={numericAmount} currency={currency} />
        </span>
    );
}

interface ERPDashboardProps {
    tenant?: {
        id: number;
        name: string;
        user_id: number;
    };
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
    storageProviders?: Array<any>;
    documents?: Array<any>;
    tasks?: Array<any>;
    notes?: Array<{
        id: number;
        title: string;
        content: string;
        category: string;
        pinned: boolean;
        date: string;
    }>;
    transactions?: Array<{
        id: number;
        reference_id: string;
        title: string;
        note: string;
        direction: string;
        amount: number;
        currency: string;
        balance_before: number;
        balance_after: number;
        reference_type: string;
        client_name: string;
        authorizer: string;
        date: string;
    }>;
    transactionStats?: {
        totalCredits: number;
        totalDebits: number;
        netFlow: number;
        txnCount: number;
    };
    hasMultiCurrency?: boolean;
    filters?: {
        search?: string;
    };
}

export default function ERPDashboard({ tenant: serverTenant, stats: serverStats, clients: serverClients, invoices: serverInvoices, chartData: serverChartData, projects: serverProjects, supportTickets: serverTickets, activityLogs: serverActivityLogs, upcomingBookings: serverBookings, storageProviders: serverStorageProviders, documents: serverDocuments, tasks: serverTasks, notes: serverNotes, transactions: serverTransactions, transactionStats: serverTransactionStats, hasMultiCurrency = false, filters = {} }: ERPDashboardProps) {
    const { toast } = useToast();
    const { auth } = usePage().props as any;
    const isTeamMember = !!auth?.team_member;
    const isReadOnlyMember = auth?.team_member && auth.team_member.role === 'member';

    // Get locked addons for the sidebar upsell
    const { lockedAddons } = useERPMenu('overview');

    const sectionMatch = typeof window !== 'undefined' ? window.location.search.match(/section=([^&]+)/) : null;
    const initialSection = sectionMatch ? sectionMatch[1] : 'overview';
    const [currentSection, setCurrentSection] = useState(initialSection);

    const handleSetSection = (section: string) => {
        setCurrentSection(section);
        if (typeof window !== 'undefined') {
            window.history.pushState({}, '', route('erp.dashboard', { section }));
        }
    };

    const handleClientSearch = (value: string) => {
        router.get(
            route('erp.dashboard'),
            { section: 'clients', search: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    useEffect(() => {
        const handlePopState = () => {
            const match = window?.location?.search?.match(/section=([^&]+)/);
            setCurrentSection(match ? match[1] : 'overview');
        };
        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, []);

    useEffect(() => {
        setActiveClients(serverClients || []);
    }, [serverClients]);

    useEffect(() => {
        setActiveInvoices(serverInvoices || []);
    }, [serverInvoices]);

    useEffect(() => {
        setProjects(serverProjects || []);
    }, [serverProjects]);

    useEffect(() => {
        setTasks(serverTasks || []);
    }, [serverTasks]);

    useEffect(() => {
        setDocuments(serverDocuments || []);
    }, [serverDocuments]);

    useEffect(() => {
        setNotes(serverNotes || []);
    }, [serverNotes]);

    useEffect(() => {
        setTransactions(serverTransactions || []);
    }, [serverTransactions]);

    const [deleteConfirm, setDeleteConfirm] = useState<{ open: boolean; client: any }>({ open: false, client: null });
    const [actionModalClient, setActionModalClient] = useState<any>(null);

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
    // WORKSPACE DB HYDRATION
    // ────────────────────────────────────────────────────────
    const [projects, setProjects] = useState<Array<any>>(serverProjects || []);
    const [newProjectForm, setNewProjectForm] = useState({
        name: '', client_id: '', budget: '', due_date: '', status: 'Planning'
    });
    const [showAddProjectModal, setShowAddProjectModal] = useState(false);

    const [editProjectForm, setEditProjectForm] = useState({
        id: null as number | null, name: '', client_id: '', budget: '', due_date: '', status: 'Planning'
    });
    const [showEditProjectModal, setShowEditProjectModal] = useState(false);
    const [deleteProjectConfirm, setDeleteProjectConfirm] = useState<{ open: boolean; project: any }>({ open: false, project: null });

    // Using real server tasks if available, fallback to empty
    const [tasks, setTasks] = useState<Array<any>>(serverTasks || []);
    const [quickTaskTitles, setQuickTaskTitles] = useState<Record<string, string>>({});

    const [expenses, setExpenses] = useState<Array<any>>([]);
    const [expenseForm, setExpenseForm] = useState({ title: '', category: 'Software', amount: '', date: '', status: 'Pending' });
    const [showAddExpenseModal, setShowAddExpenseModal] = useState(false);

    const [storageProviders, setStorageProviders] = useState<Array<any>>(serverStorageProviders || []);
    const [showAddProviderModal, setShowAddProviderModal] = useState(false);
    const [providerForm, setProviderForm] = useState({ name: '', driver: 's3', bucket: '', key: '', secret: '', endpoint: '', region: '' });

    const [documents, setDocuments] = useState<Array<any>>(serverDocuments || []);
    const [showAddDocModal, setShowAddDocModal] = useState(false);
    const [docFile, setDocFile] = useState<File | null>(null);
    const [docForm, setDocForm] = useState({ type: 'Document' });

    const [contracts, setContracts] = useState<Array<any>>([]);
    const [showAddContractModal, setShowAddContractModal] = useState(false);
    const [contractForm, setContractForm] = useState({ title: '', client: '', value: '', status: 'Draft' });

    const [notes, setNotes] = useState<Array<any>>(serverNotes || []);
    const [selectedNote, setSelectedNote] = useState<any>(null);
    const [noteEditor, setNoteEditor] = useState({ title: '', content: '', category: 'Internal' });
    const [isSavingNote, setIsSavingNote] = useState(false);

    const [supportTickets, setSupportTickets] = useState<Array<any>>(serverTickets || []);
    const [newTicketForm, setNewTicketForm] = useState({ title: '', client_id: '', client_name: '', priority: 'medium', description: '' });
    const [showAddTicketModal, setShowAddTicketModal] = useState(false);

    const [transactions, setTransactions] = useState<Array<any>>(serverTransactions || []);
    const txnStats = serverTransactionStats || { totalCredits: 0, totalDebits: 0, netFlow: 0, txnCount: 0 };
    const [txnDirectionFilter, setTxnDirectionFilter] = useState<'all' | 'CREDIT' | 'DEBIT'>('all');
    const filteredTransactions = useMemo(() => {
        if (txnDirectionFilter === 'all') return transactions;
        return transactions.filter(t => t.direction === txnDirectionFilter);
    }, [transactions, txnDirectionFilter]);

    const [teamMembers] = useState<Array<any>>([
        { id: 1, name: 'Owner', email: 'owner@workspace', role: 'Owner', status: 'Active', activities: 0 }
    ]);

    const [activityLogs, setActivityLogs] = useState<Array<any>>(serverActivityLogs || []);

    const [settingsForm, setSettingsForm] = useState({
        workspaceName: serverTenant?.name || "Musoftware Enterprise Workspace",
        taxRate: '14.00',
        defaultCurrency: stats.businessCurrency || 'USD',
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
        setDeleteConfirm({ open: true, client });
    };

    const confirmDeleteClient = () => {
        if (!deleteConfirm.client) return;
        router.delete(route('erp.clients.destroy', deleteConfirm.client.id), {
            onSuccess: () => {
                toast({ description: `Client ${deleteConfirm.client.name} deleted.` });
                prependActivity('Client Deleted', `Deleted client record ${deleteConfirm.client.name} permanently.`);
                setDeleteConfirm({ open: false, client: null });
            }
        });
    };

    const handleWalletAdjustment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedClient) return;

        let endpoint = route('erp.clients.wallet.credit', selectedClient.id);
        if (walletForm.type === 'debit') endpoint = route('erp.clients.wallet.debit', selectedClient.id);
        else if (walletForm.type === 'lock') endpoint = route('erp.clients.wallet.lock', selectedClient.id);
        else if (walletForm.type === 'unlock') endpoint = route('erp.clients.wallet.unlock', selectedClient.id);

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

    // Sync selected note content changes back to list (local preview only)
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
        if (!(quickTaskTitles[category] || '').trim()) return;
        
        let status = 'open';
        if (category === 'In Progress') status = 'in_progress';
        if (category === 'In Review') status = 'review';
        if (category === 'Done') status = 'completed';
        
        router.post(route('erp.tasks.store'), {
            title: quickTaskTitles[category],
            status: status,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setQuickTaskTitles(prev => ({ ...prev, [category]: '' }));
                toast({ description: 'Task created successfully.' });
            }
        });
    };

    // Move task category
    const moveTask = (taskId: number, direction: 'forward' | 'backward') => {
        const lanes = ['Todo', 'In Progress', 'In Review', 'Done'];
        const statuses = ['open', 'in_progress', 'review', 'completed'];
        
        let updatedTask: any = null;
        
        setTasks(prev => prev.map(t => {
            if (t.id === taskId) {
                const currentIdx = lanes.indexOf(t.category);
                let newIdx = currentIdx;
                if (direction === 'forward' && currentIdx < 3) newIdx++;
                if (direction === 'backward' && currentIdx > 0) newIdx--;
                
                const newLane = lanes[newIdx];
                if (newLane !== t.category) {
                    updatedTask = { ...t, category: newLane, _newIdx: newIdx };
                    prependActivity('Task Progressed', `Moved task "${t.title}" from "${t.category}" to "${newLane}"`);
                }
                return updatedTask ? { ...t, category: newLane } : t;
            }
            return t;
        }));
        
        if (updatedTask) {
            router.put(route('erp.tasks.update', taskId), {
                status: statuses[(updatedTask as any)._newIdx],
                task_name: updatedTask.title // Need to satisfy validation if required
            }, {
                preserveScroll: true,
                preserveState: true
            });
        }
    };

    // Log Expense
    const handleAddExpense = (e: React.FormEvent) => {
        e.preventDefault();
        // Fallback to fake state if route doesn't exist yet, but in a real operational 
        // system this should be router.post(route('erp.expenses.store'), expenseForm)
        toast({ description: 'Expense logging is coming soon.' });
        setShowAddExpenseModal(false);
        setExpenseForm({ title: '', category: 'Software', amount: '', date: '', status: 'Pending' });
    };

    // Add Storage Provider
    const handleAddProvider = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('erp.storage-providers.store'), providerForm, {
            onSuccess: () => {
                setShowAddProviderModal(false);
                setProviderForm({ name: '', driver: 's3', bucket: '', key: '', secret: '', endpoint: '', region: '' });
                toast({ description: 'Storage provider configured successfully.' });
                prependActivity('Storage Configured', `Configured AWS S3 storage provider.`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    // Add Document
    const handleAddDoc = (e: React.FormEvent) => {
        e.preventDefault();
        if (!docFile) return toast({ variant: 'destructive', description: 'Please select a file to upload.' });
        
        const formData = new FormData();
        formData.append('file', docFile);
        formData.append('type', docForm.type);

        router.post(route('erp.files.store'), formData, {
            forceFormData: true,
            onSuccess: () => {
                setShowAddDocModal(false);
                setDocFile(null);
                setDocForm({ type: 'Document' });
                toast({ description: 'File uploaded successfully.' });
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    const handleDeleteDoc = (docId: number) => {
        if (!confirm('Are you sure you want to delete this file?')) return;
        router.delete(route('erp.files.destroy', docId), {
            onSuccess: () => {
                toast({ description: 'File deleted successfully.' });
            }
        });
    };

    // Add Project
    const handleAddProject = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('erp.projects.store'), newProjectForm, {
            onSuccess: () => {
                setShowAddProjectModal(false);
                setNewProjectForm({ name: '', client_id: '', budget: '', due_date: '', status: 'Planning' });
                toast({ description: 'Project created successfully.' });
                prependActivity('Project Created', `Created project ${newProjectForm.name}.`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    // Edit Project
    const handleEditProject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editProjectForm.id) return;
        router.put(route('erp.projects.update', editProjectForm.id), editProjectForm, {
            onSuccess: () => {
                setShowEditProjectModal(false);
                setEditProjectForm({ id: null, name: '', client_id: '', budget: '', due_date: '', status: 'Planning' });
                toast({ description: 'Project updated successfully.' });
                prependActivity('Project Updated', `Updated project ${editProjectForm.name}.`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    // Confirm Delete Project
    const confirmDeleteProject = () => {
        if (!deleteProjectConfirm.project) return;
        router.delete(route('erp.projects.destroy', deleteProjectConfirm.project.id), {
            onSuccess: () => {
                toast({ description: `Project deleted successfully.` });
                prependActivity('Project Deleted', `Deleted project ${deleteProjectConfirm.project.name}.`);
                setDeleteProjectConfirm({ open: false, project: null });
            }
        });
    };

    // Draft Contract
    const handleAddContract = (e: React.FormEvent) => {
        e.preventDefault();
        toast({ description: 'Contract management is coming soon.' });
        setShowAddContractModal(false);
        setContractForm({ title: '', client: '', value: '', status: 'Draft' });
    };

    // Add Support Ticket
    const handleAddTicket = (e: React.FormEvent) => {
        e.preventDefault();
        
        const payload = {
            subject: newTicketForm.title,
            description: newTicketForm.description || `Support request for client`,
            priority: newTicketForm.priority.toLowerCase(),
            client_id: newTicketForm.client_id || null,
            client_name: newTicketForm.client_name || null,
        };

        router.post(route('erp.tickets.store'), payload, {
            onSuccess: () => {
                setShowAddTicketModal(false);
                setNewTicketForm({ title: '', client_id: '', client_name: '', priority: 'medium', description: '' });
                toast({ description: 'Support ticket opened successfully.' });
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    const handleResolveTicket = (ticketId: number) => {
        router.post(route('erp.tickets.resolve', ticketId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast({ description: 'Support ticket resolved.' });
            }
        });
    };

    const handleCloseTicket = (ticketId: number) => {
        router.post(route('erp.tickets.close', ticketId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast({ description: 'Support ticket closed.' });
            }
        });
    };

    const handleDeleteTicket = (ticketId: number) => {
        if (!confirm('Are you sure you want to delete this ticket?')) return;
        router.delete(route('erp.tickets.destroy', ticketId), {
            preserveScroll: true,
            onSuccess: () => {
                toast({ description: 'Support ticket deleted.' });
            }
        });
    };

    // Pin/Unpin Note — persisted to backend
    const togglePinNote = (noteId: number) => {
        router.post(route('erp.notes.togglePin', noteId), {}, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                prependActivity('Note Pinned', 'Note pin status updated.');
            }
        });
    };

    // Add empty Note — creates a stub in the database then selects it
    const handleCreateNote = () => {
        router.post(route('erp.notes.store'), {
            title: 'Untitled Draft Note',
            content: 'Write something here...',
            category: 'Internal',
        }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                prependActivity('Note Created', 'Created a new workspace scratchpad note.');
            }
        });
    };

    // Save Note — persist current editor state to backend
    const handleSaveNote = () => {
        if (!selectedNote) return;
        setIsSavingNote(true);
        router.put(route('erp.notes.update', selectedNote.id), noteEditor, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                toast({ description: 'Note saved successfully.' });
                prependActivity('Note Saved', `Updated note "${noteEditor.title}".`);
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            },
            onFinish: () => setIsSavingNote(false),
        });
    };

    // Delete Note — remove from backend and deselect
    const handleDeleteNote = (noteId: number) => {
        if (!confirm('Are you sure you want to delete this note?')) return;
        router.delete(route('erp.notes.destroy', noteId), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                setSelectedNote(null);
                setNoteEditor({ title: '', content: '', category: 'Internal' });
                toast({ description: 'Note deleted.' });
                prependActivity('Note Deleted', 'A workspace note was deleted.');
            }
        });
    };

    // Update Settings
    const handleUpdateSettings = () => {
        router.put(route('erp.settings.update'), settingsForm, {
            preserveScroll: true,
            onSuccess: () => {
                prependActivity('Settings Updated', `Modified workspace branded title and VAT ratios to ${settingsForm.taxRate}%`);
                toast({ description: 'Workspace parameters successfully configured.' });
            },
            onError: (errors) => {
                toast({ variant: 'destructive', description: Object.values(errors)[0] as string });
            }
        });
    };

    // ────────────────────────────────────────────────────────
    // WORKSPACE SIDEBAR SECTIONS REGISTRY
    // ────────────────────────────────────────────────────────
    const menuItems = [
        { id: 'overview', label: 'Overview', icon: LayoutDashboard },
        { id: 'clients', label: 'Clients', icon: Users },
        { id: 'projects', label: 'Projects', icon: Briefcase },
        { id: 'tasks', label: 'Tasks', icon: CheckSquare, badge: tasks.filter(t => t.category !== 'Done').length },
        { id: 'invoices', label: 'Invoices', icon: FileText, badge: activeInvoices.filter(inv => inv.status !== 'paid').length },
        { id: 'transactions', label: 'Transactions', icon: History },
        { id: 'documents', label: 'Files', icon: Folder },
        { id: 'notes', label: 'Notes', icon: Pin },
        { id: 'calendar', label: 'Calendar', icon: CalendarIcon },
        { id: 'team', label: 'Team', icon: UserCheck },
        { id: 'backup', label: 'Backup', icon: HardDrive },
        { id: 'settings', label: 'Settings', icon: Settings },
    ].filter(item => {
        if (isTeamMember && (item.id === 'team' || item.id === 'settings' || item.id === 'backup')) {
            return false;
        }
        return true;
    });

    const activeMenuLabel = useMemo(() => {
        return menuItems.find(item => item.id === currentSection)?.label 
            || lockedAddons.find(a => a.id === currentSection)?.label 
            || 'Workspace';
    }, [currentSection, menuItems, lockedAddons]);

    return (
        <ERPLayout 
            title={activeMenuLabel}
            workspaceName={settingsForm.workspaceName}
            tenantId={serverStats ? '9012' : 'DRAFT'}
            menuItems={menuItems.map(m => ({
                ...m,
                isActive: currentSection === m.id,
                href: m.id === 'team' ? route('erp.team-members.index') : (m.id === 'backup' ? route('erp.backup.index') : route('erp.dashboard', { section: m.id })),
                onClick: (m.id === 'team' || m.id === 'backup') ? undefined : (e: any) => {
                    e.preventDefault();
                    handleSetSection(m.id);
                }
            }))}
            lockedAddons={lockedAddons.map(a => ({
                ...a,
                isActive: currentSection === a.id,
                onClick: (e: any) => {
                    e.preventDefault();
                    handleSetSection(a.id);
                }
            }))}
        >
            <div className="flex-1 w-full min-w-0">

            {/* ConfirmModal for client deletion */}
            <ConfirmModal
                isOpen={deleteConfirm.open}
                title="Delete Client"
                description={`Are you sure you want to delete ${deleteConfirm.client?.name}? This will remove all linked wallets and data. This cannot be undone.`}
                confirmLabel="Delete Client"
                variant="danger"
                onConfirm={confirmDeleteClient}
                onCancel={() => setDeleteConfirm({ open: false, client: null })}
            />

            {/* ConfirmModal for project deletion */}
            <ConfirmModal
                isOpen={deleteProjectConfirm.open}
                title="Delete Project"
                description={`Are you sure you want to delete ${deleteProjectConfirm.project?.name}? This cannot be undone.`}
                confirmLabel="Delete Project"
                variant="danger"
                onConfirm={confirmDeleteProject}
                onCancel={() => setDeleteProjectConfirm({ open: false, project: null })}
            />
                        
                        {/* 1. OVERVIEW (DASHBOARD) */}
                        {currentSection === 'overview' && (
                            <div className="space-y-10">
                                
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">Overview</h2>
                                        <p className="text-sm text-slate-500 mt-1">Here's what's happening in your workspace today.</p>
                                    </div>
                                    {!isReadOnlyMember && (
                                        <div className="flex items-center gap-3">
                                            <Link href={route("erp.clients.create")}><Button size="sm" className="shadow-none">
                                                <UserPlus className="mr-2 h-4 w-4" /> Add Client
                                            </Button></Link>
                                            <Link 
                                                href={route('erp.invoices.create')}
                                                className={cn(buttonVariants({ size: 'sm' }), "shadow-sm")}
                                            >
                                                <Plus className="mr-2 h-4 w-4" /> New Invoice
                                            </Link>
                                        </div>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                    <MetricCard 
                                        label="Total Revenue"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(stats.totalRevenue)}
                                        icon={DollarSign}
                                    />
                                    <MetricCard 
                                        label="Outstanding"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(stats.outstandingRevenue)}
                                        icon={Clock}
                                    />
                                    <MetricCard 
                                        label="Active Clients"
                                        value={activeClients.length}
                                        icon={Users}
                                    />
                                    <MetricCard 
                                        label="Subscriptions"
                                        value={stats.recurringCount}
                                        icon={Layers}
                                    />
                                </div>

                                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                    
                                    {/* Main Content Column */}
                                    <div className="lg:col-span-2 space-y-8">
                                        
                                        <OperationalCard title="Active Projects" action={<button onClick={() => handleSetSection('projects')} className="text-sm text-primary hover:underline transition-colors">View all</button>}>
                                            <div className="space-y-3">
                                                {projects.filter(p => p.status === 'Active' || p.status === 'Planning').slice(0, 3).map((proj) => (
                                                    <Link href={route('erp.projects.show', proj.id)} key={proj.id} className="block group border border-border p-4 rounded-xl hover:bg-surface-raised transition-all cursor-pointer">
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
                                                    </Link>
                                                ))}
                                            </div>
                                        </OperationalCard>

                                        <OperationalCard title="Recent Invoices" noPadding action={<button onClick={() => handleSetSection('invoices')} className="text-sm text-primary hover:underline transition-colors">View all</button>}>
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
                                        </OperationalCard>
                                    </div>

                                    {/* Sidebar Column */}
                                    <div className="space-y-8">
                                        
                                        {/* Quick Actions */}
                                        <div>
                                            <h3 className="text-sm font-semibold text-slate-900 mb-4">Quick Actions</h3>
                                            <div className="space-y-2">
                                                {!isReadOnlyMember && (
                                                    <>
                                                        <Link href={route("erp.clients.create")} className="w-full flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm transition-all group">
                                                            <div className="flex items-center gap-3">
                                                                <div className="bg-slate-50 p-2 rounded-lg group-hover:bg-white transition-colors">
                                                                    <UserPlus className="h-4 w-4 text-slate-600" />
                                                                </div>
                                                                <span className="font-medium text-slate-700 text-sm">Add Client</span>
                                                            </div>
                                                            <ChevronRight className="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                                        </Link>
                                                        <Link href={route("erp.projects.create")} className="w-full flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm transition-all group">
                                                            <div className="flex items-center gap-3">
                                                                <div className="bg-slate-50 p-2 rounded-lg group-hover:bg-white transition-colors">
                                                                    <Briefcase className="h-4 w-4 text-slate-600" />
                                                                </div>
                                                                <span className="font-medium text-slate-700 text-sm">Create Project</span>
                                                            </div>
                                                            <ChevronRight className="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                                        </Link>
                                                    </>
                                                )}
                                                <button onClick={() => handleSetSection('tasks')} className="w-full flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm transition-all text-left group">
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
                                                <button onClick={() => handleSetSection('tasks')} className="text-sm text-slate-500 hover:text-slate-900 transition-colors">View all</button>
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
                                <ModulePageHeader 
                                    title="Clients" 
                                    description="Manage your clients, contacts, and their billing profiles."
                                    actions={!isReadOnlyMember && (
                                        <Link href={route("erp.clients.create")}><Button size="sm" className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Client
                                        </Button></Link>
                                    )}
                                    filters={
                                        <div className="relative w-full max-w-md">
                                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                            <Input
                                                placeholder="Search by name, email or phone..."
                                                className="pl-9 h-10 shadow-none border-transparent bg-slate-50 focus:bg-white transition-colors text-sm"
                                                defaultValue={filters?.search || ''}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter') {
                                                        handleClientSearch((e.target as HTMLInputElement).value);
                                                    }
                                                }}
                                                onBlur={(e) => handleClientSearch(e.target.value)}
                                            />
                                        </div>
                                    }
                                />

                                <OperationalCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Client</th>
                                                    <th className="px-6 py-3.5">Contact</th>
                                                    <th className="px-6 py-3.5 text-right">Balance</th>
                                                    <th className="px-6 py-3.5 text-right">Unpaid</th>
                                                    <th className="px-6 py-3.5 text-right">Paid</th>
                                                    <th className="px-6 py-3.5">Created</th>
                                                    {!isReadOnlyMember && <th className="px-6 py-3.5 text-right">Actions</th>}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {activeClients.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={isReadOnlyMember ? 6 : 7} className="p-0">
                                                            <EmptyState 
                                                                icon={Users} 
                                                                title="No Clients" 
                                                                description="Add your first client to start managing invoices and projects."
                                                            />
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    activeClients.map((client) => (
                                                        <tr key={client.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                            <td className="px-6 py-4">
                                                                <Link href={route('erp.clients.show', client.id)} className="hover:underline">
                                                                    <span className="font-semibold text-slate-900 block">{client.name}</span>
                                                                </Link>
                                                                <span className="text-slate-400 text-xs mt-0.5 block">ID: {client.id}</span>
                                                            </td>
                                                            <td className="px-6 py-4 font-mono text-xs">
                                                                <span className="block text-slate-600">{client.email}</span>
                                                                <span className="block text-slate-400 mt-0.5">{client.phone}</span>
                                                            </td>
                                                            <td className="px-6 py-4 text-right font-semibold text-slate-900 font-mono">
                                                                <CurrencyDisplay amount={client.balance ?? 0} currency={client.currency || currency} />
                                                            </td>
                                                            <td className="px-6 py-4 text-right font-mono">
                                                                {(client.unpaid ?? 0) > 0 ? (
                                                                    <span className="font-bold text-rose-600">
                                                                        <CurrencyDisplay amount={client.unpaid} currency={client.currency || currency} />
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-slate-400">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-6 py-4 text-right font-bold text-emerald-600 font-mono">
                                                                <CurrencyDisplay amount={client.totalPaid ?? 0} currency={client.currency || currency} />
                                                            </td>
                                                            <td className="px-6 py-4 text-slate-500 text-xs">{client.created_at ?? '—'}</td>
                                                            {!isReadOnlyMember && (
                                                                <td className="px-6 py-4 text-right">
                                                                    <button
                                                                        onClick={() => setActionModalClient(client)}
                                                                        className="p-1.5 hover:bg-slate-100 rounded-md text-slate-500 transition"
                                                                    >
                                                                        <MoreHorizontal className="h-4 w-4" />
                                                                    </button>
                                                                </td>
                                                            )}
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </OperationalCard>

                                {/* Client Action Modal */}
                                <Dialog open={!!actionModalClient} onOpenChange={(open) => !open && setActionModalClient(null)}>
                                    <DialogContent className="sm:max-w-lg">
                                        <DialogHeader>
                                            <DialogTitle className="flex items-center gap-3">
                                                <div className="flex items-center justify-center w-9 h-9 rounded-full bg-slate-100 text-slate-700 text-sm font-bold">
                                                    {actionModalClient?.name?.substring(0, 2)?.toUpperCase()}
                                                </div>
                                                <div>
                                                    <span className="block">{actionModalClient?.name}</span>
                                                    <span className="text-xs font-normal text-slate-400">{actionModalClient?.email}</span>
                                                </div>
                                            </DialogTitle>
                                        </DialogHeader>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                                            {/* Finance Column */}
                                            <div>
                                                <h4 className="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-3 px-1">Finance</h4>
                                                <div className="space-y-0.5">
                                                    {[
                                                        { icon: FileText, label: 'New Invoice', color: 'text-slate-600', href: route('erp.invoices.create') + '?client_id=' + actionModalClient?.id },
                                                        { icon: ArrowDownLeft, label: 'Receive Money', color: 'text-emerald-600', href: route('erp.clients.wallet.adjust', actionModalClient?.id || 0) + '?type=credit' },
                                                        { icon: ArrowUpRight, label: 'Send Money', color: 'text-amber-600', href: route('erp.clients.wallet.adjust', actionModalClient?.id || 0) + '?type=debit' },
                                                        { icon: RotateCcw, label: 'Refund', color: 'text-blue-600', href: route('erp.clients.wallet.adjust', actionModalClient?.id || 0) + '?type=refund' },
                                                        { icon: Receipt, label: 'All Invoices', color: 'text-slate-600', href: route('erp.invoices.index') + '?search=' + encodeURIComponent(actionModalClient?.name || '') },
                                                        { icon: Wallet, label: 'Transactions', color: 'text-slate-600', href: route('erp.clients.wallet.index', actionModalClient?.id || 0) },
                                                    ].map((item) => (
                                                        <Link
                                                            key={item.label}
                                                            href={item.href}
                                                            className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-50 transition-colors group"
                                                        >
                                                            <item.icon className={`h-4 w-4 ${item.color} shrink-0`} />
                                                            <span className="group-hover:translate-x-0.5 transition-transform">{item.label}</span>
                                                        </Link>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Manage Column */}
                                            <div>
                                                <h4 className="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-3 px-1">Account</h4>
                                                <div className="space-y-0.5">
                                                    {[
                                                        { icon: Eye, label: 'View Profile', color: 'text-slate-600', href: route('erp.clients.show', actionModalClient?.id || 0) },
                                                        { icon: Edit2, label: 'Edit Client', color: 'text-slate-600', href: route('erp.clients.edit', actionModalClient?.id || 0) },
                                                    ].map((item) => (
                                                        <Link
                                                            key={item.label}
                                                            href={item.href}
                                                            className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-50 transition-colors group"
                                                        >
                                                            <item.icon className={`h-4 w-4 ${item.color} shrink-0`} />
                                                            <span className="group-hover:translate-x-0.5 transition-transform">{item.label}</span>
                                                        </Link>
                                                    ))}
                                                    <div className="border-t border-slate-100 my-2" />
                                                    <button
                                                        onClick={() => {
                                                            setActionModalClient(null);
                                                            handleDeleteClient(actionModalClient);
                                                        }}
                                                        className="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] text-rose-600 hover:bg-rose-50 transition-colors group w-full text-left"
                                                    >
                                                        <Trash2 className="h-4 w-4 shrink-0" />
                                                        <span className="group-hover:translate-x-0.5 transition-transform">Delete Client</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        )}

                        {/* 3. PROJECTS & MILESTONES */}
                        {currentSection === 'projects' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Projects" 
                                    description="Manage active projects, track progress, and monitor deadlines."
                                    actions={!isReadOnlyMember && (
                                        <Link href={route("erp.projects.create")}><Button size="sm" className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> New Project
                                        </Button></Link>
                                    )}
                                />

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {projects.map((proj) => (
                                        <OperationalCard key={proj.id}>
                                            <div className="p-5 space-y-4">
                                                <div className="flex items-start justify-between">
                                                    <div>
                                                         <Link href={route('erp.projects.show', proj.id)}>
                                                             <h3 className="font-semibold text-slate-800 text-[14px] hover:text-primary hover:underline transition-colors">{proj.name}</h3>
                                                         </Link>
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
                                                    <div className="flex items-center gap-4">
                                                        <span>Budget: <span className="font-semibold text-slate-700">{formatMoney(proj.budget, 'USD')}</span></span>
                                                        <span>Deadline: <span className="font-semibold text-slate-700">{formatDate(proj.deadline)}</span></span>
                                                    </div>
                                                    {!isReadOnlyMember && (
                                                        <div className="flex items-center gap-1">
                                                            <button 
                                                                onClick={() => {
                                                                    setEditProjectForm({
                                                                        id: proj.id,
                                                                        name: proj.name,
                                                                        client_id: proj.client_id || '',
                                                                        budget: proj.budget || '',
                                                                        due_date: proj.deadline || '',
                                                                        status: proj.status || 'Planning'
                                                                    });
                                                                    setShowEditProjectModal(true);
                                                                }}
                                                                className="p-1.5 hover:bg-slate-100 rounded text-slate-500 transition-colors"
                                                                title="Edit Project"
                                                            >
                                                                <Edit2 className="h-3.5 w-3.5" />
                                                            </button>
                                                            <button 
                                                                onClick={() => setDeleteProjectConfirm({ open: true, project: proj })}
                                                                className="p-1.5 hover:bg-rose-50 rounded text-rose-500 transition-colors"
                                                                title="Delete Project"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </OperationalCard>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* 4. TASK MANAGEMENT (KANBAN) */}
                        {currentSection === 'tasks' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Tasks" 
                                    description="Manage and organize your team's tasks and priorities."
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
                                                            value={quickTaskTitles[lane] || ''}
                                                            onChange={(e) => setQuickTaskTitles(prev => ({ ...prev, [lane]: e.target.value }))}
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
                                <ModulePageHeader 
                                    title="Invoices" 
                                    description="Create, send, and track client invoices."
                                    actions={!isReadOnlyMember && (
                                        <Link 
                                            href={route('erp.invoices.create')}
                                            className={cn(buttonVariants({ size: 'sm' }), "shadow-none")}
                                        >
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> New Invoice
                                        </Link>
                                    )}
                                />

                                <OperationalCard>
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
                                                                     {!isReadOnlyMember && (
                                                                         <Link 
                                                                             href={route('erp.invoices.edit', inv.id)} 
                                                                             className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), "h-8 w-8 text-slate-400 hover:text-slate-900")}
                                                                         >
                                                                             <Edit2 className="h-4 w-4" />
                                                                         </Link>
                                                                     )}
                                                                 </div>
                                                             </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </OperationalCard>
                            </div>
                        )}

                        {/* 6. EXPENSE MANAGEMENT */}
                        {currentSection === 'expenses' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Expenses" 
                                    description="Track and log operational expenses."
                                    actions={
                                        <Link href={route("erp.expenses.create")}><Button size="sm" className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Log Expense
                                        </Button></Link>
                                    }
                                />

                                <OperationalCard>
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
                                </OperationalCard>
                            </div>
                        )}

                        {/* 7. DOCUMENT VAULT */}
                        {currentSection === 'documents' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Files" 
                                    description="Secure cloud repository for your documents and files."
                                    actions={!isReadOnlyMember && (
                                        <Link href={route("erp.files.create")}><Button size="sm" className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Upload File
                                        </Button></Link>
                                    )}
                                />

                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    {['Contracts', 'Receipts', 'Proposals', 'Tax Forms'].map((fol) => (
                                        <OperationalCard key={fol} className="hover:border-slate-300 transition cursor-pointer">
                                            <div className="p-4 flex items-center gap-3">
                                                <Folder className="h-8 w-8 text-indigo-500 shrink-0" />
                                                <div>
                                                    <span className="font-semibold text-slate-800 text-xs block">{fol}</span>
                                                    <span className="text-[10px] text-slate-400">
                                                        {fol === 'Contracts' ? contracts.length : fol === 'Receipts' ? expenses.length : 4} files
                                                    </span>
                                                </div>
                                            </div>
                                        </OperationalCard>
                                    ))}
                                </div>

                                <OperationalCard>
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
                                                                <a href={route('erp.files.show', doc.id)} target="_blank" className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), "h-8 w-8 text-slate-400 hover:text-indigo-600")} rel="noreferrer"><Cloud className="h-4 w-4" /></a>
                                                                {!isReadOnlyMember && (
                                                                    <Button variant="ghost" size="icon" onClick={() => handleDeleteDoc(doc.id)} className="h-8 w-8 text-slate-400 hover:text-rose-600"><Trash2 className="h-4 w-4" /></Button>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </OperationalCard>
                            </div>
                        )}

                        {/* 8. LEGAL AGREEMENTS */}
                        {currentSection === 'contracts' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Contracts" 
                                    description="Draft and track client service agreements and contracts."
                                    actions={
                                        <Link href={route("erp.contracts.create")}><Button size="sm" className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Draft Contract
                                        </Button></Link>
                                    }
                                />

                                <OperationalCard>
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
                                </OperationalCard>
                            </div>
                        )}

                        {/* 9. WALLET LEDGER */}
                        {currentSection === 'transactions' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Transactions" 
                                    description="View all wallet activity, payments, and balance adjustments."
                                />

                                {/* Summary Metric Cards */}
                                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                    <MetricCard
                                        label="Total Credits"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(txnStats.totalCredits)}
                                        icon={ArrowDown}
                                    />
                                    <MetricCard
                                        label="Total Debits"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(txnStats.totalDebits)}
                                        icon={ArrowUp}
                                    />
                                    <MetricCard
                                        label="Net Flow"
                                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(txnStats.netFlow)}
                                        icon={ArrowUpDown}
                                    />
                                    <MetricCard
                                        label="Total Entries"
                                        value={txnStats.txnCount}
                                        icon={History}
                                    />
                                </div>

                                {/* Filters */}
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-medium text-slate-500 mr-1">Filter:</span>
                                    {(['all', 'CREDIT', 'DEBIT'] as const).map(dir => (
                                        <button
                                            key={dir}
                                            onClick={() => setTxnDirectionFilter(dir)}
                                            className={cn(
                                                "px-3 py-1.5 rounded-lg text-xs font-semibold transition-all",
                                                txnDirectionFilter === dir
                                                    ? dir === 'CREDIT' ? 'bg-emerald-100 text-emerald-800' : dir === 'DEBIT' ? 'bg-rose-100 text-rose-800' : 'bg-slate-900 text-white'
                                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                                            )}
                                        >
                                            {dir === 'all' ? 'All' : dir === 'CREDIT' ? '↓ Credits' : '↑ Debits'}
                                        </button>
                                    ))}
                                    {txnDirectionFilter !== 'all' && (
                                        <span className="text-xs text-slate-400 ml-2">
                                            Showing {filteredTransactions.length} of {transactions.length}
                                        </span>
                                    )}
                                </div>

                                <OperationalCard noPadding>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-5 py-3.5">Reference</th>
                                                    <th className="px-5 py-3.5">Client</th>
                                                    <th className="px-5 py-3.5">Type</th>
                                                    <th className="px-5 py-3.5 text-center">Direction</th>
                                                    <th className="px-5 py-3.5 text-right">Amount</th>
                                                    <th className="px-5 py-3.5 text-right">Balance</th>
                                                    <th className="px-5 py-3.5">Authorized By</th>
                                                    <th className="px-5 py-3.5 text-right">Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {filteredTransactions.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={8} className="p-0">
                                                            <EmptyState
                                                                icon={History}
                                                                title="No transactions found"
                                                                description={txnDirectionFilter !== 'all' ? `No ${txnDirectionFilter.toLowerCase()} transactions found. Try adjusting the filter.` : "No transactions found in this workspace's ledger."}
                                                            />
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    filteredTransactions.map((txn) => (
                                                        <tr key={txn.id} className="hover:bg-slate-50/80 transition text-[13px] text-slate-700">
                                                            <td className="px-5 py-4">
                                                                <span className="font-mono font-bold text-indigo-600">{txn.reference_id}</span>
                                                            </td>
                                                            <td className="px-5 py-4">
                                                                <span className="font-semibold text-slate-900">{txn.client_name}</span>
                                                            </td>
                                                            <td className="px-5 py-4">
                                                                <div>
                                                                    <span className="font-semibold text-slate-900 block">{txn.title}</span>
                                                                    <span className="text-slate-400 text-xs block mt-0.5">{txn.note}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-5 py-4 text-center">
                                                                {txn.direction === 'CREDIT' ? (
                                                                    <Badge className="bg-emerald-50 text-emerald-700 border-none rounded font-bold text-[10px]">
                                                                        <ArrowDown className="h-3 w-3 mr-0.5" /> CREDIT
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge className="bg-rose-50 text-rose-700 border-none rounded font-bold text-[10px]">
                                                                        <ArrowUp className="h-3 w-3 mr-0.5" /> DEBIT
                                                                    </Badge>
                                                                )}
                                                            </td>
                                                            <td className="px-5 py-4 text-right">
                                                                <FinancialAmount amount={txn.direction === 'DEBIT' ? -txn.amount : txn.amount} currency={txn.currency || currency} colorize={true} />
                                                            </td>
                                                            <td className="px-5 py-4 text-right">
                                                                <div className="flex items-center justify-end gap-1 text-xs font-mono">
                                                                    <span className="text-slate-400"><CurrencyDisplay amount={txn.balance_before} currency={txn.currency || currency} /></span>
                                                                    <ChevronRight className="h-3 w-3 text-slate-300" />
                                                                    <span className="text-slate-700 font-semibold"><CurrencyDisplay amount={txn.balance_after} currency={txn.currency || currency} /></span>
                                                                </div>
                                                            </td>
                                                            <td className="px-5 py-4">
                                                                <div className="flex items-center gap-2">
                                                                    <div className="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-[9px] font-bold">
                                                                        {txn.authorizer?.substring(0, 2)?.toUpperCase()}
                                                                    </div>
                                                                    <span className="text-slate-600 font-medium text-xs">{txn.authorizer}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-5 py-4 text-right text-slate-400 font-mono text-xs">
                                                                {txn.date}
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </OperationalCard>
                            </div>
                        )}

                        {/* 10. TEAM ACCESS CONTROL */}
                        {currentSection === 'team' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Team" 
                                    description="Manage your team members and their workspace access."
                                />

                                <div className="grid grid-cols-1 lg:grid-cols-10 gap-6">
                                    <div className="lg:col-span-6 space-y-6">
                                        <OperationalCard>
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
                                        </OperationalCard>
                                    </div>

                                    {/* Permissions definitions card */}
                                    <div className="lg:col-span-4 space-y-6">
                                        <OperationalCard>
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
                                        </OperationalCard>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* 11. UNIVERSAL NOTES SYSTEM */}
                        {currentSection === 'notes' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Notes" 
                                    description="Workspace scratchpad for logging internal memos and pinned notes."
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
                                            <OperationalCard className="p-6 space-y-4">
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
                                                    <div className="flex items-center gap-1.5 shrink-0 ml-3">
                                                        <Button
                                                            size="sm"
                                                            onClick={handleSaveNote}
                                                            disabled={isSavingNote}
                                                            className="h-7 px-3 text-xs shadow-none"
                                                        >
                                                            {isSavingNote ? 'Saving...' : 'Save'}
                                                        </Button>
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            onClick={() => handleDeleteNote(selectedNote.id)}
                                                            className="h-7 w-7 text-slate-400 hover:text-rose-600"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                <Textarea 
                                                    className="border-none font-sans text-[13px] min-h-[250px] focus-visible:ring-0 p-0 shadow-none leading-relaxed text-slate-600"
                                                    placeholder="Start typing your note here..."
                                                    value={noteEditor.content}
                                                    onChange={(e) => setNoteEditor(prev => ({ ...prev, content: e.target.value }))}
                                                />
                                            </OperationalCard>
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
                                <ModulePageHeader 
                                    title="Calendar" 
                                    description="View task due dates, contract timelines, and upcoming events."
                                />

                                <OperationalCard>
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
                                </OperationalCard>
                            </div>
                        )}

                        {/* 13. SUPPORT TICKETS */}
                        {currentSection === 'support' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Support" 
                                    description="Track client helpdesk tickets and resolve inquiries."
                                    actions={
                                        <Link href={route("erp.tickets.create")}><Button size="sm" className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Open Ticket
                                        </Button></Link>
                                    }
                                />

                                <OperationalCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Ticket ID</th>
                                                    <th className="px-6 py-3.5">Ticket Subject</th>
                                                    <th className="px-6 py-3.5">Client Tenant</th>
                                                    <th className="px-6 py-3.5">Priority</th>
                                                    <th className="px-6 py-3.5">Status</th>
                                                    <th className="px-6 py-3.5">Date Created</th>
                                                    <th className="px-6 py-3.5 text-right">Actions</th>
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
                                                                t.priority.toLowerCase() === 'high' || t.priority.toLowerCase() === 'urgent' ? 'bg-rose-50 text-rose-700' :
                                                                t.priority.toLowerCase() === 'medium' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'
                                                            }`}>
                                                                {t.priority}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <Badge className={`text-[10px] uppercase font-bold tracking-wider rounded ${
                                                                t.status.toLowerCase() === 'resolved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                                t.status.toLowerCase() === 'closed' ? 'bg-slate-100 text-slate-400 border-slate-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'
                                                            }`}>
                                                                {t.status}
                                                            </Badge>
                                                        </td>
                                                        <td className="px-6 py-4 text-slate-400 font-mono text-xs">{formatDate(t.date)}</td>
                                                        <td className="px-6 py-4 text-right">
                                                            <div className="flex justify-end gap-1.5">
                                                                {(t.status.toLowerCase() !== 'resolved' && t.status.toLowerCase() !== 'closed') && (
                                                                    <Button 
                                                                        size="xs" 
                                                                        variant="ghost" 
                                                                        className="h-7 px-2 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 text-xs shadow-none border-none"
                                                                        onClick={() => handleResolveTicket(t.id)}
                                                                    >
                                                                        Resolve
                                                                    </Button>
                                                                )}
                                                                {t.status.toLowerCase() !== 'closed' && (
                                                                    <Button 
                                                                        size="xs" 
                                                                        variant="ghost" 
                                                                        className="h-7 px-2 text-amber-600 hover:text-amber-700 hover:bg-amber-50 text-xs shadow-none border-none"
                                                                        onClick={() => handleCloseTicket(t.id)}
                                                                    >
                                                                        Close
                                                                    </Button>
                                                                )}
                                                                <Button 
                                                                    size="xs" 
                                                                    variant="ghost" 
                                                                    className="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 text-xs shadow-none border-none"
                                                                    onClick={() => handleDeleteTicket(t.id)}
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </OperationalCard>
                            </div>
                        )}

                        {/* 14. AUDIT TIMELINE */}
                        {currentSection === 'activity' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Activity" 
                                    description="Log of actions taken within the workspace."
                                />

                                <OperationalCard className="p-6">
                                    <ActivityTimeline items={activityLogs} />
                                </OperationalCard>
                            </div>
                        )}

                        {/* 15. WORKSPACE SETTINGS */}
                        {currentSection === 'settings' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Settings" 
                                    description="Manage your workspace preferences, billing parameters, and brand identity."
                                />

                                <OperationalCard>
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
                                            <Button size="sm" className="shadow-none" onClick={handleUpdateSettings}>
                                                Save Settings
                                            </Button>
                                        </div>
                                    </div>
                                </OperationalCard>

                                <OperationalCard>
                                    <div className="p-6 border-b border-slate-100 flex items-center justify-between">
                                        <div>
                                            <h3 className="font-semibold text-slate-900 text-sm">Storage Integrations</h3>
                                            <p className="text-xs text-slate-500 mt-1">Connect your workspace directly to external cloud storage (S3 compatible).</p>
                                        </div>
                                        <Link href={route("erp.storage-providers.create")}><Button size="sm" variant="outline" className="shadow-sm">
                                            <Plus className="h-3.5 w-3.5 mr-1.5" /> Add Provider
                                        </Button></Link>
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
                                </OperationalCard>
                            </div>
                        )}

                        {/* LOCKED ADDON UPGRADE CARD */}
                        {(() => {
                            const activeAddon = lockedAddons.find(a => a.id === currentSection);
                            if (!activeAddon) return null;
                            const AddonIcon = activeAddon.icon;
                            return (
                                <Card className="border-indigo-200/50 bg-gradient-to-br from-slate-50 to-indigo-50/30 shadow-none overflow-hidden relative mt-2">
                                    <div className="absolute top-0 right-0 translate-x-16 -translate-y-16 opacity-[0.04] pointer-events-none">
                                        <AddonIcon className="h-72 w-72 text-indigo-600" />
                                    </div>
                                    <CardContent className="p-8 md:p-10 relative z-10 space-y-6">
                                        <div className="flex items-center gap-2">
                                            <Lock className="h-5 w-5 text-indigo-600" />
                                            <span className="font-semibold text-indigo-600 text-sm tracking-wide">Premium Feature</span>
                                        </div>
                                        <div className="space-y-3">
                                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 leading-tight">
                                                {activeAddon.label}
                                            </h1>
                                            <p className="text-slate-500 leading-relaxed max-w-2xl">
                                                {activeAddon.description || `Unlock ${activeAddon.label} to enhance your workspace with powerful capabilities.`}
                                            </p>
                                        </div>
                                        {activeAddon.features && activeAddon.features.length > 0 && (
                                            <div className="flex flex-wrap gap-4 text-sm text-slate-600">
                                                {activeAddon.features.map((f: string, i: number) => (
                                                    <div key={i} className="flex items-center gap-2">
                                                        <CheckCircle2 className="h-4 w-4 text-indigo-500" />
                                                        {f}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        <div className="pt-2">
                                            <Link href={route('subscriptions.plans')}>
                                                <Button className="shadow-none flex items-center gap-2 group h-11 px-8 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                                                    Unlock {activeAddon.label} for 500 EGP/Yr
                                                    <ArrowUpRight className="h-4 w-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
                                                </Button>
                                            </Link>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })()}

            </div>
        </ERPLayout>
    );
}
