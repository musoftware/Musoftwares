import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
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
    Cloud, Database, Link as LinkIcon, HardDrive, Key, CheckCircle, SearchCode, Lock
} from 'lucide-react';
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
}

export default function ERPDashboard({ tenant: serverTenant, stats: serverStats, clients: serverClients, invoices: serverInvoices, chartData: serverChartData, projects: serverProjects, supportTickets: serverTickets, activityLogs: serverActivityLogs, upcomingBookings: serverBookings, storageProviders: serverStorageProviders, documents: serverDocuments, tasks: serverTasks, notes: serverNotes }: ERPDashboardProps) {
    const { toast } = useToast();
    const { auth } = usePage().props as any;
    const isTeamMember = !!auth?.team_member;
    const isReadOnlyMember = auth?.team_member && auth.team_member.role === 'member';

    const sectionMatch = typeof window !== 'undefined' ? window.location.search.match(/section=([^&]+)/) : null;
    const initialSection = sectionMatch ? sectionMatch[1] : 'overview';
    const [currentSection, setCurrentSection] = useState(initialSection);

    const handleSetSection = (section: string) => {
        setCurrentSection(section);
        if (typeof window !== 'undefined') {
            window.history.pushState({}, '', route('erp.dashboard', { section }));
        }
    };

    useEffect(() => {
        const handlePopState = () => {
            const match = window?.location?.search?.match(/section=([^&]+)/);
            setCurrentSection(match ? match[1] : 'overview');
        };
        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, []);

    const [deleteConfirm, setDeleteConfirm] = useState<{ open: boolean; client: any }>({ open: false, client: null });

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
    const [quickTaskTitle, setQuickTaskTitle] = useState('');

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
        if (!quickTaskTitle.trim()) return;
        
        let status = 'open';
        if (category === 'In Progress') status = 'in_progress';
        if (category === 'In Review') status = 'review';
        if (category === 'Done') status = 'completed';
        
        router.post(route('erp.tasks.store'), {
            title: quickTaskTitle,
            status: status,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setQuickTaskTitle('');
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
        { id: 'clients', label: 'Clients', icon: Users, badge: activeClients.length },
        { id: 'projects', label: 'Projects', icon: Briefcase, badge: projects.filter(p => p.status === 'Active').length },
        { id: 'tasks', label: 'Tasks', icon: CheckSquare, badge: tasks.filter(t => t.category !== 'Done').length },
        { id: 'invoices', label: 'Invoices', icon: FileText, badge: activeInvoices.length },
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
        return menuItems.find(item => item.id === currentSection)?.label || 'Workspace';
    }, [currentSection, menuItems]);

    return (
        <WorkspaceLayout 
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
                                                    <div key={proj.id} className="group border border-border p-4 rounded-xl hover:bg-surface-raised transition-all cursor-pointer" onClick={() => handleSetSection('projects')}>
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
                                        <Button size="sm" onClick={() => setShowAddClientModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Client
                                        </Button>
                                    )}
                                />

                                <OperationalCard>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                    <th className="px-6 py-3.5">Client / Company</th>
                                                    <th className="px-6 py-3.5">Contact Detail</th>
                                                    <th className="px-6 py-3.5">Address</th>
                                                    <th className="px-6 py-3.5 text-right">Invoiced</th>
                                                    <th className="px-6 py-3.5 text-right">Paid</th>
                                                    {!isReadOnlyMember && <th className="px-6 py-3.5 text-center">Wallet Action</th>}
                                                    {!isReadOnlyMember && <th className="px-6 py-3.5 text-right">Control</th>}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {activeClients.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={isReadOnlyMember ? 5 : 7} className="p-0">
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
                                                                <CurrencyDisplay amount={client.totalInvoiced} currency={currency} />
                                                            </td>
                                                            <td className="px-6 py-4 text-right font-bold text-emerald-600 font-mono">
                                                                <CurrencyDisplay amount={client.totalPaid} currency={currency} />
                                                            </td>
                                                            {!isReadOnlyMember && (
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
                                                            )}
                                                            {!isReadOnlyMember && (
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
                                                            )}
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </OperationalCard>
                            </div>
                        )}

                        {/* 3. PROJECTS & MILESTONES */}
                        {currentSection === 'projects' && (
                            <div className="space-y-6">
                                <ModulePageHeader 
                                    title="Projects" 
                                    description="Manage active projects, track progress, and monitor deadlines."
                                    actions={!isReadOnlyMember && (
                                        <Button size="sm" onClick={() => setShowAddProjectModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> New Project
                                        </Button>
                                    )}
                                />

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {projects.map((proj) => (
                                        <OperationalCard key={proj.id}>
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
                                        <Button size="sm" onClick={() => setShowAddExpenseModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Log Expense
                                        </Button>
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
                                        <Button size="sm" onClick={() => setShowAddDocModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Upload File
                                        </Button>
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
                                        <Button size="sm" onClick={() => setShowAddContractModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Draft Contract
                                        </Button>
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

                                <OperationalCard>
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
                                        <Button size="sm" onClick={() => setShowAddTicketModal(true)} className="shadow-none">
                                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Open Ticket
                                        </Button>
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
                                </OperationalCard>
                            </div>
                        )}

            {/* ────────────────────────────────────────────────────────
                MODALS AND OVERLAYS SECTION
                ──────────────────────────────────────────────────────── */}
            
            {/* ADD CLIENT MODAL */}
            {showAddClientModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                    <OperationalCard className="w-full max-w-md shadow-2xl animate-scale-up">
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
                    </OperationalCard>
                </div>
            )}

            {/* EDIT CLIENT MODAL */}
            {showEditClientModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
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
                    </OperationalCard>
                </div>
            )}

            {/* ADD PROJECT MODAL */}
            {showAddProjectModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                    <OperationalCard className="w-full max-w-md shadow-2xl animate-scale-up">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Create New Project</h3>
                            <button onClick={() => setShowAddProjectModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleAddProject} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Project Name</label>
                                <Input required placeholder="Website Redesign" value={newProjectForm.name} onChange={e => setNewProjectForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={newProjectForm.client_id}
                                    onChange={e => setNewProjectForm(prev => ({ ...prev, client_id: e.target.value }))}
                                    required
                                >
                                    <option value="" disabled>Select a Client</option>
                                    {activeClients.map(c => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={newProjectForm.status}
                                    onChange={e => setNewProjectForm(prev => ({ ...prev, status: e.target.value }))}
                                >
                                    <option value="Planning">Planning</option>
                                    <option value="Active">Active</option>
                                    <option value="On Hold">On Hold</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Budget ($)</label>
                                    <Input type="number" min="0" step="0.01" placeholder="5000.00" value={newProjectForm.budget} onChange={e => setNewProjectForm(prev => ({ ...prev, budget: e.target.value }))} className="shadow-none" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Deadline</label>
                                    <Input type="date" value={newProjectForm.due_date} onChange={e => setNewProjectForm(prev => ({ ...prev, due_date: e.target.value }))} className="shadow-none" />
                                </div>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddProjectModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Create Project</Button>
                            </div>
                        </form>
                    </OperationalCard>
                </div>
            )}

            {/* EDIT PROJECT MODAL */}
            {showEditProjectModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">Edit Project</h3>
                            <button onClick={() => setShowEditProjectModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleEditProject} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Project Name</label>
                                <Input required placeholder="Website Redesign" value={editProjectForm.name} onChange={e => setEditProjectForm(prev => ({ ...prev, name: e.target.value }))} className="shadow-none" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={editProjectForm.client_id}
                                    onChange={e => setEditProjectForm(prev => ({ ...prev, client_id: e.target.value }))}
                                    required
                                >
                                    <option value="" disabled>Select a Client</option>
                                    {activeClients.map(c => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={editProjectForm.status}
                                    onChange={e => setEditProjectForm(prev => ({ ...prev, status: e.target.value }))}
                                >
                                    <option value="Planning">Planning</option>
                                    <option value="Active">Active</option>
                                    <option value="On Hold">On Hold</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Budget ($)</label>
                                    <Input type="number" min="0" step="0.01" placeholder="5000.00" value={editProjectForm.budget} onChange={e => setEditProjectForm(prev => ({ ...prev, budget: e.target.value }))} className="shadow-none" />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Deadline</label>
                                    <Input type="date" value={editProjectForm.due_date} onChange={e => setEditProjectForm(prev => ({ ...prev, due_date: e.target.value }))} className="shadow-none" />
                                </div>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowEditProjectModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Update Project</Button>
                            </div>
                        </form>
                    </OperationalCard>
                </div>
            )}

            {/* ADJUST CLIENT WALLET MODAL */}
            {showWalletModal && selectedClient && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                    <OperationalCard className="w-full max-w-md shadow-2xl animate-scale-up">
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
                    </OperationalCard>
                </div>
            )}



            {/* LOG EXPENSE MODAL */}
            {showAddExpenseModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
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
                    </OperationalCard>
                </div>
            )}

            {/* ADD DOCUMENT VAULT FILE MODAL */}
            {showAddDocModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
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
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Select File</label>
                                <Input required type="file" onChange={e => setDocFile(e.target.files ? e.target.files[0] : null)} className="shadow-none py-1.5" />
                            </div>
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Classification</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-none"
                                    value={docForm.type}
                                    onChange={e => setDocForm(prev => ({ ...prev, type: e.target.value }))}
                                >
                                    <option value="Document">General Document</option>
                                    <option value="Contract">Master Agreement</option>
                                    <option value="Proposal">Client Proposal</option>
                                    <option value="Receipt">Expense Receipt</option>
                                    <option value="Tax">Corporate Tax Sheet</option>
                                </select>
                            </div>
                            <div className="pt-4 border-t flex justify-end gap-2">
                                <Button type="button" variant="outline" size="sm" className="shadow-none" onClick={() => setShowAddDocModal(false)}>Cancel</Button>
                                <Button type="submit" size="sm" className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"><Cloud className="h-4 w-4 mr-1.5" /> Upload to Bucket</Button>
                            </div>
                        </form>
                    </OperationalCard>
                </div>
            )}

            {/* ADD STORAGE PROVIDER MODAL */}
            {showAddProviderModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
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
                    </OperationalCard>
                </div>
            )}

            {/* DRAFT CONTRACT AGREEMENT MODAL */}
            {showAddContractModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
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
                    </OperationalCard>
                </div>
            )}

            {/* OPEN TICKET MODAL */}
            {showAddTicketModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <OperationalCard className="w-full max-w-md shadow-2xl">
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
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={newTicketForm.client_id}
                                    onChange={e => setNewTicketForm(prev => ({ 
                                        ...prev, 
                                        client_id: e.target.value, 
                                        client_name: e.target.value === '' ? prev.client_name : '' 
                                    }))}
                                >
                                    <option value="">-- New Client (Quick Create) --</option>
                                    {activeClients.map(c => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                            {newTicketForm.client_id === '' && (
                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">New Client Name</label>
                                    <Input required placeholder="Nexus Tech Inc" value={newTicketForm.client_name} onChange={e => setNewTicketForm(prev => ({ ...prev, client_name: e.target.value }))} className="shadow-none" />
                                </div>
                            )}
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Description</label>
                                <Textarea required placeholder="Describe the issue in detail..." value={newTicketForm.description} onChange={e => setNewTicketForm(prev => ({ ...prev, description: e.target.value }))} className="shadow-none h-20" />
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
                    </OperationalCard>
                </div>
            )}

        </div>
        </WorkspaceLayout>
    );
}
