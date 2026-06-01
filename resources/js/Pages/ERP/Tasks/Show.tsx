import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import axios from 'axios';
import {
    ChevronLeft,
    Plus,
    CheckCircle2,
    Circle,
    Calendar,
    Clock,
    DollarSign,
    Pause,
    Play,
    Trash2,
    Edit3,
    Tag,
    AlertCircle,
    Loader2,
    MoreVertical,
    FileText,
    TrendingUp,
    X
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { __ } from '@/lib/i18n';

interface Task {
    id: number;
    task_name: string;
    task_description: string | null;
    status: 'open' | 'in_progress' | 'review' | 'completed' | 'archived';
    priority: 'low' | 'normal' | 'high' | 'urgent';
    archived: boolean;
    due_date: string | null;
    created_at: string;
    client: { id: number; name: string } | null;
    project: { id: number; name: string } | null;
    creator: string | null;
    todos_count: number;
    total_todos: number;
    completed_todos: number;
    progress: number | null;
}

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
    children: TodoItem[];
    created_at: string;
}

interface ShowProps {
    task: Task;
    todos: TodoItem[];
    completion: number;
    currencies?: Array<{ id: number; currency: string; symbol: string }>;
}

export default function Show({ task: initialTask, todos: initialTodos, completion: initialCompletion, currencies = [] }: ShowProps) {
    const [task, setTask] = useState<Task>(initialTask);
    const [todos, setTodos] = useState<TodoItem[]>(initialTodos);
    const [completion, setCompletion] = useState<number>(initialCompletion);
    
    // UI states
    const [isEditBoardOpen, setIsEditBoardOpen] = useState(false);
    const [isAddTodoOpen, setIsAddTodoOpen] = useState(false);
    const [editingTodo, setEditingTodo] = useState<TodoItem | null>(null);
    const [submittingTodo, setSubmittingTodo] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Edit board details form
    const boardForm = useForm({
        task_name: task.task_name,
        task_description: task.task_description || '',
        priority: task.priority,
        status: task.status,
        due_date: task.due_date ? new Date(task.due_date).toISOString().split('T')[0] : '',
    });

    // Add/Edit todo form state
    const [todoData, setTodoData] = useState({
        title: '',
        description: '',
        priority: 'normal' as 'low' | 'normal' | 'high' | 'urgent',
        cost: '',
        cost_currency: 'USD',
        start_at: '',
        end_at: '',
        tagsInput: '',
    });

    const handleEditBoardSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        boardForm.put(route('erp.tasks.update', task.id), {
            onSuccess: (page: any) => {
                setIsEditBoardOpen(false);
                if (page.props.task) {
                    setTask(page.props.task);
                }
            }
        });
    };

    // Quick recalculate local stats based on actual array
    const recalculateCompletion = (currentTodos: TodoItem[]) => {
        const total = currentTodos.length;
        if (total === 0) {
            setCompletion(0);
            return;
        }
        const completed = currentTodos.filter(t => t.completed).length;
        setCompletion(Math.round((completed / total) * 100));
    };

    // Toggle complete
    const handleToggleComplete = async (todo: TodoItem) => {
        const targetCompleted = !todo.completed;
        
        // Optimistic UI update
        const updatedTodos = todos.map(t => {
            if (t.id === todo.id) {
                return { ...t, completed: targetCompleted, completed_at: targetCompleted ? new Date().toISOString() : null };
            }
            return t;
        });
        setTodos(updatedTodos);
        recalculateCompletion(updatedTodos);

        try {
            const response = await axios.post(`/erp/tasks/${task.id}/items/${todo.id}/complete`, {
                completed: targetCompleted
            });
            if (response.data.success) {
                // Keep backend response item synced
                setTodos(todos.map(t => t.id === todo.id ? response.data.item : t));
            }
        } catch (err: any) {
            // Revert on error
            setTodos(todos);
            recalculateCompletion(todos);
            setErrorMessage(err.response?.data?.message || "Failed to update item status.");
        }
    };

    // Toggle pause
    const handleTogglePause = async (todo: TodoItem) => {
        const targetPaused = !todo.paused;
        
        // Optimistic UI update
        setTodos(todos.map(t => t.id === todo.id ? { ...t, paused: targetPaused } : t));

        try {
            const endpoint = `/erp/tasks/${task.id}/items/${todo.id}/${targetPaused ? 'pause' : 'resume'}`;
            const response = await axios.post(endpoint);
            if (response.data.success) {
                // Keep backend response item synced
                setTodos(todos.map(t => t.id === todo.id ? { ...t, paused: targetPaused } : t));
            }
        } catch (err: any) {
            // Revert
            setTodos(todos);
            setErrorMessage(err.response?.data?.message || "Failed to toggle pause status.");
        }
    };

    // Delete item
    const handleDeleteTodo = async (todo: TodoItem) => {
        if (todo.is_paid) {
            alert("Paid items cannot be deleted.");
            return;
        }
        
        if (confirm("Are you sure you want to delete this todo item?")) {
            // Optimistic
            const filteredTodos = todos.filter(t => t.id !== todo.id);
            setTodos(filteredTodos);
            recalculateCompletion(filteredTodos);

            try {
                const response = await axios.delete(`/erp/tasks/${task.id}/items/${todo.id}`);
                if (!response.data.success) {
                    // Revert
                    setTodos(todos);
                    recalculateCompletion(todos);
                    setErrorMessage(response.data.message || "Failed to delete item.");
                }
            } catch (err: any) {
                setTodos(todos);
                recalculateCompletion(todos);
                setErrorMessage(err.response?.data?.message || "Failed to delete item.");
            }
        }
    };

    // Delete entire board
    const handleDeleteBoard = () => {
        if (confirm("Are you sure you want to delete this task board? All todo items will be permanently removed.")) {
            import('@inertiajs/react').then(({ router }) => {
                router.delete(route('erp.tasks.destroy', task.id));
            });
        }
    };

    // Open create todo modal
    const openAddTodo = () => {
        setTodoData({
            title: '',
            description: '',
            priority: 'normal',
            cost: '',
            cost_currency: 'USD',
            start_at: '',
            end_at: '',
            tagsInput: '',
        });
        setEditingTodo(null);
        setIsAddTodoOpen(true);
    };

    // Open edit todo modal
    const openEditTodo = (todo: TodoItem) => {
        setTodoData({
            title: todo.title,
            description: todo.description || '',
            priority: todo.priority,
            cost: todo.cost ? todo.cost.toString() : '',
            cost_currency: todo.cost_currency || 'USD',
            start_at: todo.start_at ? todo.start_at.split('T')[0] : '',
            end_at: todo.end_at ? todo.end_at.split('T')[0] : '',
            tagsInput: todo.tags ? todo.tags.join(', ') : '',
        });
        setEditingTodo(todo);
        setIsAddTodoOpen(true);
    };

    // Handle Add/Edit Todo Form Submit
    const handleTodoSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmittingTodo(true);
        setErrorMessage(null);

        const tags = todoData.tagsInput
            ? todoData.tagsInput.split(',').map(t => t.trim()).filter(t => t !== '')
            : [];

        const payload = {
            title: todoData.title,
            description: todoData.description || null,
            priority: todoData.priority,
            cost: todoData.cost ? parseFloat(todoData.cost) : null,
            cost_currency: todoData.cost ? todoData.cost_currency : null,
            start_at: todoData.start_at || null,
            end_at: todoData.end_at || null,
            tags: tags,
        };

        try {
            if (editingTodo) {
                // Update
                const response = await axios.put(`/erp/tasks/${task.id}/items/${editingTodo.id}`, payload);
                if (response.data.success) {
                    const updated = todos.map(t => t.id === editingTodo.id ? response.data.item : t);
                    setTodos(updated);
                    recalculateCompletion(updated);
                    setIsAddTodoOpen(false);
                }
            } else {
                // Create
                const response = await axios.post(`/erp/tasks/${task.id}/items`, payload);
                if (response.data.success) {
                    const updated = [...todos, response.data.item];
                    setTodos(updated);
                    recalculateCompletion(updated);
                    setIsAddTodoOpen(false);
                }
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.message || "Validation failed or server encountered an error.");
        } finally {
            setSubmittingTodo(false);
        }
    };

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
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('tasks');

    return (
        <ERPLayout title={`ERP Task: ${task.task_name}`} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-[1000px] mx-auto px-4 py-8 space-y-8 font-sans text-sm">
                
                {/* Back button & quick meta */}
                <div className="flex items-center justify-between">
                    <Link 
                        href={route('erp.tasks.index')} 
                        className="text-muted-foreground hover:text-foreground text-xs flex items-center gap-1 transition-colors"
                    >
                        <ChevronLeft className="h-4 w-4" />{__('general.back_to_task_boards')}</Link>

                    {errorMessage && (
                        <div className="flex items-center gap-1.5 text-rose-600 bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-md text-xs animate-shake">
                            <AlertCircle className="h-4 w-4" />
                            <span>{errorMessage}</span>
                            <button onClick={() => setErrorMessage(null)} className="ml-2 text-rose-400 hover:text-rose-600">
                                <X className="h-3 w-3" />
                            </button>
                        </div>
                    )}
                </div>

                {/* Board overview hero card */}
                <Card className="shadow-none border-border relative overflow-hidden bg-card">
                    <div className="absolute top-0 right-0 p-6 flex gap-2">
                        <Button 
                            onClick={() => setIsEditBoardOpen(true)}
                            variant="outline" 
                            size="sm"
                            className="shadow-none h-8 text-xs gap-1.5"
                        >
                            <Edit3 className="h-3.5 w-3.5" />{__('general.edit_board')}</Button>
                        <Button 
                            onClick={handleDeleteBoard}
                            variant="outline" 
                            size="sm"
                            className="shadow-none h-8 text-xs gap-1.5 text-rose-500 hover:text-rose-600 hover:bg-rose-50 border-border"
                            title={__('general.delete_board')}
                        >
                            <Trash2 className="h-3.5 w-3.5" />{__('general.delete_board')}</Button>
                    </div>

                    <CardContent className="p-6 md:p-8 space-y-6">
                        <div className="space-y-2 max-w-[80%]">
                            <Badge variant="outline" className="border-primary/20 text-primary bg-primary/5 uppercase font-bold text-[10px] tracking-wider">
                                Task Board #{task.id}
                            </Badge>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground leading-tight">
                                {task.task_name}
                            </h1>
                            <p className="text-muted-foreground text-xs leading-relaxed max-w-2xl">
                                {task.task_description || "Add a description to detail what this task board accomplishes for your organization."}
                            </p>
                        </div>

                        {/* Visual statistics grid */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-border pt-6">
                            <div className="space-y-1">
                                <span className="text-[10px] text-muted-foreground uppercase font-bold tracking-wider">{__('general.client_assignee')}</span>
                                <div className="text-xs font-semibold text-foreground flex items-center gap-1.5">
                                    <FileText className="h-4 w-4 text-primary/70" />
                                    <span>{task.client?.name || "Not assigned"}</span>
                                </div>
                            </div>
                            
                            <div className="space-y-1">
                                <span className="text-[10px] text-muted-foreground uppercase font-bold tracking-wider">{__('general.target_due_date')}</span>
                                <div className="text-xs font-semibold text-foreground flex items-center gap-1.5">
                                    <Calendar className="h-4 w-4 text-primary/70" />
                                    <span>{task.due_date || "No limit"}</span>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <span className="text-[10px] text-muted-foreground uppercase font-bold tracking-wider">{__('general.board_status')}</span>
                                <div className="pt-0.5">
                                    <Badge className="bg-primary/10 text-primary border-primary/20 shadow-none capitalize font-semibold text-[10px]">{task.status.replace('_', ' ')}</Badge>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <span className="text-[10px] text-muted-foreground uppercase font-bold tracking-wider">{__('general.created_by')}</span>
                                <div className="text-xs font-semibold text-foreground italic">
                                    {task.creator || "System"}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Progress bar and statistics detail */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Completion percent card */}
                    <Card className="shadow-none border-border md:col-span-1 bg-muted/10">
                        <CardHeader className="p-5 pb-3">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{__('general.completion_ratio')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-5 pt-0 space-y-4">
                            <div className="flex items-baseline gap-2">
                                <span className="text-4xl font-extrabold text-foreground tracking-tight">{completion}%</span>
                                <span className="text-muted-foreground text-xs font-medium">Finished</span>
                            </div>

                            <div className="h-2 w-full bg-border rounded-full overflow-hidden">
                                <div 
                                    className="h-full bg-emerald-500 rounded-full transition-all duration-500" 
                                    style={{ width: `${completion}%` }}
                                />
                            </div>

                            <div className="flex justify-between items-center text-xs text-muted-foreground border-t border-border/60 pt-3">
                                <span>{__('general.total_tasks')}</span>
                                <span className="font-semibold text-foreground">{todos.length}</span>
                            </div>
                            <div className="flex justify-between items-center text-xs text-muted-foreground">
                                <span>{__('general.completed_items')}</span>
                                <span className="font-semibold text-emerald-600">{todos.filter(t => t.completed).length}</span>
                            </div>
                            <div className="flex justify-between items-center text-xs text-muted-foreground">
                                <span>{__('general.pending_open')}</span>
                                <span className="font-semibold text-amber-600">{todos.filter(t => !t.completed).length}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Todo checklist panel */}
                    <Card className="shadow-none border-border md:col-span-2">
                        <CardHeader className="p-5 pb-2 flex flex-row items-center justify-between">
                            <div className="space-y-0.5">
                                <CardTitle className="text-sm font-bold">{__('general.board_checklist_items')}</CardTitle>
                                <CardDescription className="text-[11px]">{__('general.track_itemized_milestones_or_deliverables_for_this_board')}</CardDescription>
                            </div>
                            <Button 
                                onClick={openAddTodo}
                                size="sm" 
                                className="shadow-none h-8 text-xs gap-1"
                            >
                                <Plus className="h-3.5 w-3.5" />{__('general.add_item')}</Button>
                        </CardHeader>
                        <CardContent className="p-5 pt-0 space-y-2">
                            {todos.length === 0 ? (
                                <div className="py-12 text-center text-muted-foreground text-xs italic space-y-2">
                                    <FileText className="h-8 w-8 text-muted-foreground/30 mx-auto" />
                                    <p>{__('general.no_checklist_items_added_yet_click_quot_add_item_quot_to_populate_your_board')}</p>
                                </div>
                            ) : (
                                <div className="space-y-3 pt-2">
                                    <AnimatePresence initial={false}>
                                        {todos.map((todo) => (
                                            <motion.div
                                                key={todo.id}
                                                initial={{ opacity: 0, height: 0 }}
                                                animate={{ opacity: 1, height: 'auto' }}
                                                exit={{ opacity: 0, height: 0 }}
                                                transition={{ duration: 0.2 }}
                                                className={`group rounded-lg border p-4 transition-all flex items-start justify-between gap-4 ${
                                                    todo.completed 
                                                        ? 'bg-muted/30 border-muted text-muted-foreground' 
                                                        : todo.paused 
                                                            ? 'bg-amber-50/20 border-amber-100 text-foreground'
                                                            : 'bg-card border-border hover:border-primary/20 text-foreground'
                                                }`}
                                            >
                                                <div className="flex items-start gap-3 flex-grow max-w-[85%]">
                                                    {/* Custom Checkbox */}
                                                    <button 
                                                        onClick={() => handleToggleComplete(todo)}
                                                        className="mt-0.5 text-muted-foreground hover:text-primary transition-colors flex-shrink-0"
                                                    >
                                                        {todo.completed ? (
                                                            <CheckCircle2 className="h-5 w-5 text-emerald-500 fill-emerald-50" />
                                                        ) : (
                                                            <Circle className="h-5 w-5" />
                                                        )}
                                                    </button>

                                                    <div className="space-y-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span className={`font-semibold text-xs ${todo.completed ? 'line-through text-muted-foreground' : 'text-foreground'}`}>
                                                                {todo.title}
                                                            </span>
                                                            
                                                            {getPriorityBadge(todo.priority)}
                                                            
                                                            {todo.paused && (
                                                                <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-0.5"><Pause className="h-2 w-2" /> Paused</span>
                                                            )}
                                                            
                                                            {todo.is_paid && (
                                                                <span className="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-800 border border-emerald-200">{__('general.paid_milestone')}</span>
                                                            )}
                                                        </div>

                                                        {todo.description && (
                                                            <p className="text-[11px] text-muted-foreground leading-normal">
                                                                {todo.description}
                                                            </p>
                                                        )}

                                                        {/* Details Line */}
                                                        <div className="flex flex-wrap items-center gap-3 pt-2 text-[10px] text-muted-foreground font-medium">
                                                            {todo.cost && (
                                                                <span className="flex items-center gap-0.5 bg-muted/60 border border-border px-1.5 py-0.5 rounded text-foreground font-semibold">
                                                                    <DollarSign className="h-3 w-3 text-emerald-600" />
                                                                    {todo.cost} {todo.cost_currency}
                                                                </span>
                                                            )}

                                                            {(todo.start_at || todo.end_at) && (
                                                                <span className="flex items-center gap-1 bg-muted/60 border border-border px-1.5 py-0.5 rounded">
                                                                    <Calendar className="h-3 w-3" />
                                                                    {todo.start_at ? new Date(todo.start_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}) : 'Start'} 
                                                                    <span>—</span> 
                                                                    {todo.end_at ? new Date(todo.end_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}) : 'End'}
                                                                </span>
                                                            )}

                                                            {todo.tags && todo.tags.length > 0 && (
                                                                <span className="flex items-center gap-1">
                                                                    <Tag className="h-3 w-3 opacity-60" />
                                                                    <span className="gap-1 flex">{todo.tags.map((tag, i) => (
                                                                        <span key={i} className="hover:text-primary">#{tag}</span>
                                                                    ))}</span>
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Hover Action Panel */}
                                                <div className="flex items-center gap-1.5 flex-shrink-0 opacity-40 group-hover:opacity-100 transition-opacity">
                                                    <Button 
                                                        onClick={() => handleTogglePause(todo)}
                                                        variant="ghost" 
                                                        size="icon" 
                                                        className="h-8 w-8 text-zinc-500 hover:text-amber-600 hover:bg-amber-50"
                                                        title={todo.paused ? "Resume Todo" : "Pause Todo"}
                                                    >
                                                        {todo.paused ? <Play className="h-3.5 w-3.5" /> : <Pause className="h-3.5 w-3.5" />}
                                                    </Button>
                                                    
                                                    <Button 
                                                        onClick={() => openEditTodo(todo)}
                                                        variant="ghost" 
                                                        size="icon" 
                                                        className="h-8 w-8 text-zinc-500 hover:text-primary"
                                                        title={__('general.edit_details')}
                                                    >
                                                        <Edit3 className="h-3.5 w-3.5" />
                                                    </Button>

                                                    <Button 
                                                        onClick={() => handleDeleteTodo(todo)}
                                                        variant="ghost" 
                                                        size="icon" 
                                                        className="h-8 w-8 text-rose-500 hover:text-rose-700 hover:bg-rose-50 disabled:opacity-30"
                                                        title={__('general.delete_todo')}
                                                        disabled={todo.is_paid}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </motion.div>
                                        ))}
                                    </AnimatePresence>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Edit Task Board Details Dialog Modal */}
            <Dialog open={isEditBoardOpen} onOpenChange={setIsEditBoardOpen}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Edit3 className="h-5 w-5 text-primary" />{__('general.edit_board_details')}</DialogTitle>
                        <DialogDescription className="text-xs">{__('general.update_the_global_settings_title_status_and_due_date_of_this_client_task_board')}</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEditBoardSubmit} className="space-y-4 py-2 text-xs">
                        {/* Name */}
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_task_name" className="text-xs font-semibold text-foreground">{__('general.task_board_title')}</Label>
                            <Input 
                                id="edit_task_name"
                                value={boardForm.data.task_name}
                                onChange={(e) => boardForm.setData('task_name', e.target.value)}
                                className="shadow-none h-9 text-xs"
                                required
                            />
                            {boardForm.errors.task_name && <p className="text-rose-500 text-[11px] font-medium">{boardForm.errors.task_name}</p>}
                        </div>

                        {/* Description */}
                        <div className="space-y-1.5">
                            <Label htmlFor="edit_task_description" className="text-xs font-semibold text-foreground">Description</Label>
                            <Textarea 
                                id="edit_task_description"
                                value={boardForm.data.task_description}
                                onChange={(e) => boardForm.setData('task_description', e.target.value)}
                                className="shadow-none text-xs min-h-[85px] resize-none"
                            />
                            {boardForm.errors.task_description && <p className="text-rose-500 text-[11px] font-medium">{boardForm.errors.task_description}</p>}
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            {/* Priority */}
                            <div className="space-y-1.5 col-span-1">
                                <Label htmlFor="edit_priority" className="text-xs font-semibold text-foreground">Priority</Label>
                                <select
                                    id="edit_priority"
                                    value={boardForm.data.priority}
                                    onChange={(e) => boardForm.setData('priority', e.target.value as any)}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            {/* Status */}
                            <div className="space-y-1.5 col-span-1">
                                <Label htmlFor="edit_status" className="text-xs font-semibold text-foreground">Status</Label>
                                <select
                                    id="edit_status"
                                    value={boardForm.data.status}
                                    onChange={(e) => boardForm.setData('status', e.target.value as any)}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="open">Open</option>
                                    <option value="in_progress">{__('general.in_progress')}</option>
                                    <option value="review">Review</option>
                                    <option value="completed">Completed</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            {/* Due Date */}
                            <div className="space-y-1.5 col-span-1">
                                <Label htmlFor="edit_due_date" className="text-xs font-semibold text-foreground">{__('general.due_date')}</Label>
                                <Input 
                                    id="edit_due_date"
                                    type="date"
                                    value={boardForm.data.due_date}
                                    onChange={(e) => boardForm.setData('due_date', e.target.value)}
                                    className="shadow-none h-9 text-xs"
                                />
                            </div>
                        </div>

                        <DialogFooter className="pt-4 border-t border-border mt-4 flex sm:justify-between items-center w-full">
                            <Button 
                                type="button" 
                                variant="ghost" 
                                onClick={handleDeleteBoard}
                                className="shadow-none text-xs text-rose-500 hover:text-rose-600 hover:bg-rose-50 px-2"
                                disabled={boardForm.processing}
                            >
                                <Trash2 className="h-3.5 w-3.5 mr-1.5" />{__('general.delete_board')}</Button>
                            <div className="flex items-center gap-2">
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    onClick={() => setIsEditBoardOpen(false)}
                                    className="shadow-none text-xs"
                                    disabled={boardForm.processing}
                                >
                                    Cancel
                                </Button>
                                <Button 
                                    type="submit" 
                                    className="shadow-none text-xs gap-2"
                                    disabled={boardForm.processing}
                                >
                                    {boardForm.processing && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                                    Save Changes
                                </Button>
                            </div>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Add / Edit Todo Item Dialog Modal */}
            <Dialog open={isAddTodoOpen} onOpenChange={setIsAddTodoOpen}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            {editingTodo ? <Edit3 className="h-5 w-5 text-primary" /> : <Plus className="h-5 w-5 text-primary" />}
                            {editingTodo ? "Edit Todo Checklist Item" : "Add Todo Checklist Item"}
                        </DialogTitle>
                        <DialogDescription className="text-xs">{__('general.define_specific_actions_cost_estimates_milestone_dates_or_tags_for_this_item')}</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleTodoSubmit} className="space-y-4 py-2 text-xs">
                        {/* Title */}
                        <div className="space-y-1.5">
                            <Label htmlFor="todo_title" className="text-xs font-semibold text-foreground">{__('general.todo_title_task')}</Label>
                            <Input 
                                id="todo_title"
                                value={todoData.title}
                                onChange={(e) => setTodoData({ ...todoData, title: e.target.value })}
                                placeholder={__('general.e_g_integrate_stripe_checkout_sdk')}
                                className="shadow-none h-9 text-xs"
                                required
                            />
                        </div>

                        {/* Description */}
                        <div className="space-y-1.5">
                            <Label htmlFor="todo_description" className="text-xs font-semibold text-foreground">Description (Optional)</Label>
                            <Textarea 
                                id="todo_description"
                                value={todoData.description}
                                onChange={(e) => setTodoData({ ...todoData, description: e.target.value })}
                                placeholder={__('general.add_specific_technical_steps_links_or_context')}
                                className="shadow-none text-xs min-h-[70px] resize-none"
                            />
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            {/* Priority */}
                            <div className="space-y-1.5 col-span-1">
                                <Label htmlFor="todo_priority" className="text-xs font-semibold text-foreground">Priority</Label>
                                <select
                                    id="todo_priority"
                                    value={todoData.priority}
                                    onChange={(e) => setTodoData({ ...todoData, priority: e.target.value as any })}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            {/* Cost */}
                            <div className="space-y-1.5 col-span-1">
                                <Label htmlFor="todo_cost" className="text-xs font-semibold text-foreground">{__('general.milestone_price')}</Label>
                                <Input 
                                    id="todo_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={todoData.cost}
                                    onChange={(e) => setTodoData({ ...todoData, cost: e.target.value })}
                                    placeholder="0.00"
                                    className="shadow-none h-9 text-xs"
                                />
                            </div>

                            {/* Cost Currency */}
                            <div className="space-y-1.5 col-span-1">
                                <Label htmlFor="todo_currency" className="text-xs font-semibold text-foreground">Currency</Label>
                                <select
                                    id="todo_currency"
                                    value={todoData.cost_currency}
                                    onChange={(e) => setTodoData({ ...todoData, cost_currency: e.target.value })}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring"
                                >
                                    {currencies.map((c) => (
                                        <option key={c.id} value={c.currency}>
                                            {c.currency} ({c.symbol})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            {/* Start At */}
                            <div className="space-y-1.5">
                                <Label htmlFor="todo_start_at" className="text-xs font-semibold text-foreground">{__('general.start_date')}</Label>
                                <Input 
                                    id="todo_start_at"
                                    type="date"
                                    value={todoData.start_at}
                                    onChange={(e) => setTodoData({ ...todoData, start_at: e.target.value })}
                                    className="shadow-none h-9 text-xs"
                                />
                            </div>

                            {/* End At */}
                            <div className="space-y-1.5">
                                <Label htmlFor="todo_end_at" className="text-xs font-semibold text-foreground">{__('general.completion_date')}</Label>
                                <Input 
                                    id="todo_end_at"
                                    type="date"
                                    value={todoData.end_at}
                                    onChange={(e) => setTodoData({ ...todoData, end_at: e.target.value })}
                                    className="shadow-none h-9 text-xs"
                                />
                            </div>
                        </div>

                        {/* Tags */}
                        <div className="space-y-1.5">
                            <Label htmlFor="todo_tags" className="text-xs font-semibold text-foreground">{__('general.tags_keywords')}</Label>
                            <Input 
                                id="todo_tags"
                                value={todoData.tagsInput}
                                onChange={(e) => setTodoData({ ...todoData, tagsInput: e.target.value })}
                                placeholder={__('general.comma_separated_e_g_backend_security_api')}
                                className="shadow-none h-9 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-4 border-t border-border mt-4">
                            <Button 
                                type="button" 
                                variant="outline" 
                                onClick={() => setIsAddTodoOpen(false)}
                                className="shadow-none text-xs"
                                disabled={submittingTodo}
                            >
                                Cancel
                            </Button>
                            <Button 
                                type="submit" 
                                className="shadow-none text-xs gap-2"
                                disabled={submittingTodo}
                            >
                                {submittingTodo && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                                {editingTodo ? "Save Todo" : "Add Todo"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ERPLayout>
    );
}
