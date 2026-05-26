import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import axios from 'axios';
import {
    Plus,
    CheckCircle2,
    Circle,
    Calendar,
    DollarSign,
    Pause,
    Edit3,
    AlertCircle,
    X,
    ChevronDown,
    FileText,
    User,
    ListTodo,
    HandCoins,
    Archive,
    Share2,
    FilePlus2
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

interface TodoItem {
    id: number;
    title: string;
    description: string | null;
    completed: boolean;
    completed_at: string | null;
    priority: 'low' | 'normal' | 'high' | 'urgent';
    priority_color: string | null;
    sort_index: number;
    paused: boolean;
    is_paid: boolean;
    cost: number | null;
    cost_currency: string | null;
    start_at: string | null;
    end_at: string | null;
    tags: string[];
    parent_id: number | null;
    created_at: string;
}

interface TaskData {
    id: number;
    task_name: string;
    status: string;
    client_id: number;
    todos: TodoItem[];
}

interface ClientData {
    client: {
        id: number;
        name: string;
        email: string;
    };
    tasks: TaskData[];
}

interface AsListProps {
    arrangedClients: ClientData[];
}

export default function AsList({ arrangedClients: initialData }: AsListProps) {
    const [clientsData, setClientsData] = useState<ClientData[]>(initialData);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const getPriorityBadge = (priority: string) => {
        switch (priority) {
            case 'urgent':
                return <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-rose-50 border border-rose-200 text-rose-700">Urgent</span>;
            case 'high':
                return <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-50 border border-amber-200 text-amber-700">High</span>;
            case 'normal':
                return <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-blue-50 border border-blue-200 text-blue-700">Normal</span>;
            default:
                return <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-slate-50 border border-slate-200 text-slate-500">Low</span>;
        }
    };

    const handleToggleComplete = async (clientId: number, taskId: number, todo: TodoItem) => {
        const targetCompleted = !todo.completed;

        // Optimistic UI update
        const updatedClientsData = clientsData.map(c => {
            if (c.client.id === clientId) {
                return {
                    ...c,
                    tasks: c.tasks.map(t => {
                        if (t.id === taskId) {
                            return {
                                ...t,
                                todos: t.todos.map(item => {
                                    if (item.id === todo.id) {
                                        return { ...item, completed: targetCompleted, completed_at: targetCompleted ? new Date().toISOString() : null };
                                    }
                                    return item;
                                })
                            };
                        }
                        return t;
                    })
                };
            }
            return c;
        });
        
        setClientsData(updatedClientsData);

        try {
            const response = await axios.post(`/erp/tasks/${taskId}/items/${todo.id}/complete`, {
                completed: targetCompleted
            });
            if (!response.data.success) {
                throw new Error("Failed to update status");
            }
        } catch (err: any) {
            // Revert on error
            setClientsData(clientsData);
            setErrorMessage(err.response?.data?.message || "Failed to update item status.");
        }
    };

    return (
        <AuthenticatedLayout header="Current Tasks">
            <Head title="Current Tasks As List" />

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-8 font-sans text-sm">
                
                {/* Header Section */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Current Tasks</h1>
                        <p className="text-muted-foreground text-xs mt-1">List view of all active checklist items grouped by client and task.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button asChild size="sm" className="h-9 gap-1.5">
                            <Link href={route('erp.tasks.index')}>
                                <Plus className="h-4 w-4" /> Add New Task
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm" className="h-9 gap-1.5 bg-card">
                            <Link href={route('erp.tasks.index')}>
                                <ListTodo className="h-4 w-4" /> View As Summary
                            </Link>
                        </Button>
                    </div>
                </div>

                {errorMessage && (
                    <div className="flex items-center gap-1.5 text-rose-600 bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-md text-xs animate-shake">
                        <AlertCircle className="h-4 w-4" />
                        <span>{errorMessage}</span>
                        <button onClick={() => setErrorMessage(null)} className="ml-auto text-rose-400 hover:text-rose-600">
                            <X className="h-3 w-3" />
                        </button>
                    </div>
                )}

                {/* Main List Container */}
                <Card className="shadow-none border-border bg-card overflow-hidden">
                    <CardContent className="p-0">
                        {clientsData.length === 0 ? (
                            <div className="py-16 text-center text-muted-foreground text-xs italic space-y-2">
                                <ListTodo className="h-10 w-10 text-muted-foreground/30 mx-auto" />
                                <p>No active checklist items found.</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-border">
                                {clientsData.map((clientGroup) => (
                                    <div key={clientGroup.client.id} className="p-4 md:p-6 bg-card">
                                        
                                        {/* Client Header Dropdown */}
                                        <div className="mb-4">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="secondary" size="sm" className="gap-1 font-bold">
                                                        {clientGroup.client.name}
                                                        <ChevronDown className="h-3.5 w-3.5 opacity-70" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="start" className="w-56 text-xs">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('erp.clients.show', clientGroup.client.id)} className="flex items-center gap-2 cursor-pointer">
                                                            <User className="h-4 w-4 text-primary" /> Profile
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('erp.tasks.index', { client_id: clientGroup.client.id })} className="flex items-center gap-2 cursor-pointer">
                                                            <ListTodo className="h-4 w-4 text-info" /> Tasks
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('erp.invoices.index', { client_id: clientGroup.client.id })} className="flex items-center gap-2 cursor-pointer">
                                                            <FileText className="h-4 w-4 text-secondary" /> Invoices
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('erp.wallet.show', clientGroup.client.id)} className="flex items-center gap-2 cursor-pointer">
                                                            <HandCoins className="h-4 w-4 text-success" /> Receive Money
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('erp.invoices.create', { client_id: clientGroup.client.id })} className="flex items-center gap-2 cursor-pointer">
                                                            <Plus className="h-4 w-4 text-primary" /> New Invoice
                                                        </Link>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>

                                        {/* Tasks for this client */}
                                        <div className="space-y-4">
                                            {clientGroup.tasks.map(task => (
                                                <div key={task.id} className="border border-border rounded-lg overflow-hidden shadow-sm">
                                                    {/* Task Header */}
                                                    <div className="bg-muted/30 px-4 py-3 border-b border-border flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                                                        <div className="font-semibold text-sm flex items-center gap-2">
                                                            <span>📌 {task.task_name}</span>
                                                            <Badge className="bg-primary/10 text-primary border-primary/20 shadow-none text-[10px] capitalize font-medium">{task.status}</Badge>
                                                        </div>
                                                        
                                                        {/* Task Actions Toolbar */}
                                                        <div className="flex flex-wrap items-center gap-1.5">
                                                            <Button asChild size="sm" variant="default" className="h-7 text-[10px] px-2.5 bg-blue-600 hover:bg-blue-700">
                                                                <Link href={route('erp.tasks.index')}>
                                                                    <Plus className="h-3 w-3 mr-1" /> New Task
                                                                </Link>
                                                            </Button>
                                                            <Button asChild size="sm" variant="default" className="h-7 text-[10px] px-2.5 bg-purple-600 hover:bg-purple-700">
                                                                <Link href={route('erp.tasks.show', task.id)}>
                                                                    <Share2 className="h-3 w-3 mr-1" /> Share
                                                                </Link>
                                                            </Button>
                                                            <Button asChild size="sm" variant="default" className="h-7 text-[10px] px-2.5 bg-green-600 hover:bg-green-700">
                                                                <Link href={route('erp.tasks.show', task.id)}>
                                                                    <FilePlus2 className="h-3 w-3 mr-1" /> Add Todo
                                                                </Link>
                                                            </Button>
                                                            <Button asChild size="sm" variant="default" className="h-7 text-[10px] px-2.5 bg-slate-500 hover:bg-slate-600">
                                                                <Link href={route('erp.tasks.show', task.id)}>
                                                                    <Archive className="h-3 w-3 mr-1" /> Archive
                                                                </Link>
                                                            </Button>
                                                        </div>
                                                    </div>

                                                    {/* Todos List */}
                                                    <div className="bg-card p-3 space-y-2">
                                                        <AnimatePresence initial={false}>
                                                            {task.todos.map((todo) => (
                                                                <motion.div
                                                                    key={todo.id}
                                                                    initial={{ opacity: 0, height: 0 }}
                                                                    animate={{ opacity: 1, height: 'auto' }}
                                                                    exit={{ opacity: 0, height: 0 }}
                                                                    transition={{ duration: 0.2 }}
                                                                    className={`group rounded border p-3 transition-all flex items-start justify-between gap-3 ${
                                                                        todo.completed 
                                                                            ? 'bg-muted/30 border-muted text-muted-foreground hidden' // typically completed aren't shown, but just in case
                                                                            : todo.paused 
                                                                                ? 'bg-amber-50/20 border-amber-100 text-foreground'
                                                                                : 'bg-card border-border hover:border-primary/20 text-foreground'
                                                                    }`}
                                                                >
                                                                    <div className="flex items-start gap-3 flex-grow max-w-[85%]">
                                                                        <button 
                                                                            onClick={() => handleToggleComplete(clientGroup.client.id, task.id, todo)}
                                                                            className="mt-0.5 text-muted-foreground hover:text-primary transition-colors flex-shrink-0"
                                                                        >
                                                                            {todo.completed ? (
                                                                                <CheckCircle2 className="h-4 w-4 text-emerald-500 fill-emerald-50" />
                                                                            ) : (
                                                                                <Circle className="h-4 w-4" />
                                                                            )}
                                                                        </button>

                                                                        <div className="space-y-1 w-full">
                                                                            <div className="flex flex-wrap items-center gap-2">
                                                                                <span className={`font-medium text-xs ${todo.completed ? 'line-through text-muted-foreground' : 'text-foreground'}`}>
                                                                                    {todo.title}
                                                                                </span>
                                                                                
                                                                                {getPriorityBadge(todo.priority)}
                                                                                
                                                                                {todo.paused && (
                                                                                    <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-0.5">
                                                                                        <Pause className="h-2 w-2" /> Paused
                                                                                    </span>
                                                                                )}
                                                                                
                                                                                {todo.is_paid && (
                                                                                    <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-800 border border-emerald-200">Paid</span>
                                                                                )}
                                                                            </div>

                                                                            {todo.description && (
                                                                                <p className="text-[11px] text-muted-foreground leading-normal mt-1">
                                                                                    {todo.description}
                                                                                </p>
                                                                            )}

                                                                            {/* Details Line */}
                                                                            <div className="flex flex-wrap items-center gap-3 pt-1.5 text-[10px] text-muted-foreground font-medium">
                                                                                {todo.cost && (
                                                                                    <span className="flex items-center gap-0.5 text-emerald-600 font-semibold">
                                                                                        <DollarSign className="h-3 w-3" />
                                                                                        {todo.cost} {todo.cost_currency}
                                                                                    </span>
                                                                                )}

                                                                                {(todo.start_at || todo.end_at) && (
                                                                                    <span className="flex items-center gap-1 opacity-80">
                                                                                        <Calendar className="h-3 w-3" />
                                                                                        {todo.start_at ? new Date(todo.start_at).toLocaleDateString() : 'Start'} 
                                                                                        <span>—</span> 
                                                                                        {todo.end_at ? new Date(todo.end_at).toLocaleDateString() : 'End'}
                                                                                    </span>
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    {/* Quick link to the task board */}
                                                                    <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                                                                        <Button asChild variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-primary">
                                                                            <Link href={route('erp.tasks.show', task.id)} title="Go to Task Board">
                                                                                <Edit3 className="h-3.5 w-3.5" />
                                                                            </Link>
                                                                        </Button>
                                                                    </div>
                                                                </motion.div>
                                                            ))}
                                                        </AnimatePresence>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
