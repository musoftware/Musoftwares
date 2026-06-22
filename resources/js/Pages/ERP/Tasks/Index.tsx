import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'framer-motion';
import {
  Plus,
  FolderKanban,
  Calendar,
  User,
  CheckCircle2,
  Clock,
  AlertTriangle,
  Archive,
  Trash2,
  X,
  Filter,
  ChevronRight,
  Loader2 } from
'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
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
  client: {id: number;name: string;} | null;
  project: {id: number;name: string;} | null;
  creator: string | null;
  todos_count: number;
  total_todos: number;
  completed_todos: number;
  progress: number | null;
}

interface Client {
  id: number;
  name: string;
}

interface Project {
  id: number;
  name: string;
  client_id: number;
}

interface IndexProps {
  tasks: {
    data: Task[];
    links: any;
    current_page: number;
    last_page: number;
  };
  clients: Client[];
  projects: Project[];
  filters: {
    client_id?: string;
    status?: string;
    show_archived?: string;
  };
}

export default function Index({ tasks, clients, projects = [], filters }: IndexProps) {
  const [isCreateOpen, setIsCreateOpen] = useState(false);

  // Filters form
  const filterForm = useForm({
    client_id: filters.client_id || 'all',
    status: filters.status || 'all',
    show_archived: filters.show_archived === 'true'
  });

  // Create task form
  const { data, setData, post, processing, errors, reset } = useForm({
    task_name: '',
    task_description: '',
    client_id: '',
    project_id: '',
    priority: 'normal',
    status: 'open',
    due_date: ''
  });

  const handleFilterChange = (key: 'client_id' | 'status' | 'show_archived', value: any) => {
    const newFilters = { ...filterForm.data, [key]: value };
    filterForm.setData(key, value);

    // Build query parameters
    const params: any = {};
    if (newFilters.client_id !== 'all') params.client_id = newFilters.client_id;
    if (newFilters.status !== 'all') params.status = newFilters.status;
    if (newFilters.show_archived) params.show_archived = 'true';

    // Perform Inertia get request
    routerGet(route('erp.tasks.index'), params);
  };

  // Safe router get wrapper since we can't import router directly sometimes depending on setup
  const routerGet = (url: string, params: any) => {
    import('@inertiajs/react').then(({ router }) => {
      router.get(url, params, { preserveState: true });
    });
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('erp.tasks.store'), {
      onSuccess: () => {
        setIsCreateOpen(false);
        reset();
      }
    });
  };

  const handleArchive = (id: number) => {
    import('@inertiajs/react').then(({ router }) => {
      router.post(route('erp.tasks.archive', id), {}, {
        onSuccess: () => {}
      });
    });
  };

  const handleUnarchive = (id: number) => {
    import('@inertiajs/react').then(({ router }) => {
      router.post(route('erp.tasks.unarchive', id), {}, {
        onSuccess: () => {}
      });
    });
  };

  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this task board? All todo items will be permanently removed.')) {
      import('@inertiajs/react').then(({ router }) => {
        router.delete(route('erp.tasks.destroy', id));
      });
    }
  };

  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'urgent':
        return <Badge variant="destructive" className="bg-rose-500 hover:bg-rose-600 text-white font-medium uppercase text-[10px]">{__('general.urgent')}</Badge>;
      case 'high':
        return <Badge variant="secondary" className="bg-amber-500 hover:bg-amber-600 text-white font-medium uppercase text-[10px]">{__('general.high')}</Badge>;
      case 'normal':
        return <Badge variant="secondary" className="bg-blue-500 hover:bg-blue-600 text-white font-medium uppercase text-[10px]">{__('general.normal')}</Badge>;
      default:
        return <Badge variant="outline" className="text-muted-foreground uppercase text-[10px]">Low</Badge>;
    }
  };

  const getStatusBadge = (status: string) => {
    const classes = "text-xs font-semibold px-2 py-0.5 rounded shadow-none border";
    switch (status) {
      case 'completed':
        return <span className={`${classes} bg-emerald-50 text-emerald-700 border-emerald-200`}>{__('general.completed')}</span>;
      case 'review':
        return <span className={`${classes} bg-indigo-50 text-indigo-700 border-indigo-200`}>{__('general.review')}</span>;
      case 'in_progress':
        return <span className={`${classes} bg-blue-50 text-blue-700 border-blue-200`}>{__('general.in_progress')}</span>;
      case 'archived':
        return <span className={`${classes} bg-zinc-100 text-zinc-700 border-zinc-300`}>{__('general.archived')}</span>;
      default:
        return <span className={`${classes} bg-slate-50 text-slate-700 border-slate-200`}>{__('general.open')}</span>;
    }
  };
  const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('tasks');

  return (
    <ERPLayout title={__('general.erp_client_task_boards')} workspaceName={workspaceName} tenantId={tenantId} menuItems={menuItems} lockedAddons={lockedAddons}>

            <div className="max-w-[1200px] mx-auto px-4 py-8 space-y-6 font-sans text-sm">
                
                {/* Header Action Panel */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4 gap-4">
                    <div className="me-auto space-y-1">
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <FolderKanban className="h-6 w-6 text-primary" />{__('general.client_tasks_todos')}</h1>
                        <p className="text-muted-foreground text-xs leading-normal">{__('general.create_track_and_manage_interactive_task_boards_and_todo_items_shared_with_your_clients')}</p>
                    </div>
                    
                    <Button
            onClick={() => setIsCreateOpen(true)}
            className="shadow-none flex items-center gap-2 h-10 px-5">
            
                        <Plus className="h-4 w-4" />{__('general.create_task_board')}</Button>
                </div>

                {/* Filters Section */}
                <Card className="shadow-none border-border">
                    <CardContent className="p-4 flex flex-wrap gap-4 items-center justify-end gap-4">
                        <div className="me-auto flex flex-wrap gap-4 items-center">
                            <div className="flex items-center gap-2 text-muted-foreground text-xs">
                                <Filter className="h-3.5 w-3.5" />
                                <span>Filter By:</span>
                            </div>

                            {/* Client Filter */}
                            <div className="w-[250px]">
                                <ClientAutocomplete
                  value={filterForm.data.client_id === 'all' ? '' : filterForm.data.client_id}
                  onChange={(val) => handleFilterChange('client_id', val || 'all')}
                  placeholder={__('general.all_clients')} />
                
                            </div>

                            {/* Status Filter */}
                            <div className="w-[150px]">
                                <Select
                  value={filterForm.data.status}
                  onValueChange={(val) => handleFilterChange('status', val)}>
                  
                                    <SelectTrigger className="h-9 shadow-none text-xs">
                                        <SelectValue placeholder={__('general.all_statuses')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{__('general.all_statuses')}</SelectItem>
                                        <SelectItem value="open">{__('general.open')}</SelectItem>
                                        <SelectItem value="in_progress">{__('general.in_progress')}</SelectItem>
                                        <SelectItem value="review">{__('general.review')}</SelectItem>
                                        <SelectItem value="completed">{__('general.completed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Show Archived Toggle */}
                        <div className="flex items-center gap-2">
                            <label className="flex items-center gap-2 cursor-pointer text-xs text-muted-foreground select-none">
                                <input
                  type="checkbox"
                  checked={filterForm.data.show_archived}
                  onChange={(e) => handleFilterChange('show_archived', e.target.checked)}
                  className="rounded border-input text-primary shadow-none focus:ring-primary h-3.5 w-3.5" />
                {__('general.show_archived_boards')}</label>
                        </div>
                    </CardContent>
                </Card>

                {/* Grid of Task Boards */}
                {(tasks.data as any).length === 0 ?
        <Card className="shadow-none border-dashed border-2 py-16 text-center">
                        <CardContent className="space-y-4">
                            <div className="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                                <FolderKanban className="h-6 w-6" />
                            </div>
                            <div className="space-y-1 max-w-sm mx-auto">
                                <h3 className="font-semibold text-foreground text-sm">{__('general.no_task_boards_found')}</h3>
                                <p className="text-muted-foreground text-xs leading-normal">
                                    {filterForm.data.client_id !== 'all' || filterForm.data.status !== 'all' ?
                "No boards match your current filters. Try resetting them." :
                "Start collaborating with clients by creating your first interactive task board."}
                                </p>
                            </div>
                            <Button
              onClick={() => setIsCreateOpen(true)}
              variant="outline"
              className="shadow-none text-xs gap-2">
              
                                <Plus className="h-3.5 w-3.5" />{__('general.create_board')}</Button>
                        </CardContent>
                    </Card> :

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {(tasks.data as any).map((task) =>
          <motion.div
            key={task.id}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.2 }}>
            
                                <Card className="shadow-none border border-border hover:border-primary/20 hover:shadow-md/5 transition-all h-[240px] flex flex-col justify-end gap-4 overflow-hidden relative group">
                                    {task.archived &&
              <div className="absolute top-0 end-0 bg-zinc-100 border-s border-b border-zinc-200 text-zinc-600 px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase rounded-bs">
                                            {__('general.archived')}</div>
              }
                                    
                                    <CardHeader className="me-auto p-5 pb-3 space-y-2">
                                        <div className="flex items-start justify-end gap-4 gap-2">
                                            <div className="me-auto space-y-1">
                                                <h3 className="font-bold text-foreground text-base tracking-tight leading-snug line-clamp-1 group-hover:text-primary transition-colors">
                                                    <Link href={route('erp.tasks.show', task.id)} className="hover:underline">
                                                        {task.task_name}
                                                    </Link>
                                                </h3>
                                                
                                                {task.client &&
                    <div className="flex items-center gap-1.5 text-muted-foreground text-xs">
                                                        <User className="h-3.5 w-3.5 text-primary/60" />
                                                        <span className="font-medium text-foreground/80">{task.client.name}</span>
                                                    </div>
                    }
                                            </div>
                                        </div>

                                        <p className="text-muted-foreground text-xs leading-normal line-clamp-2 min-h-[32px]">
                                            {task.task_description || "No description provided."}
                                        </p>
                                    </CardHeader>

                                    <CardContent className="p-5 pt-0 space-y-4 flex-grow flex flex-col justify-end">
                                        {/* Progress Bar */}
                                        {task.total_todos > 0 ?
                <div className="space-y-1.5">
                                                <div className="flex justify-end gap-4 items-center text-[11px] font-medium">
                                                    <span className="me-auto text-muted-foreground">{__('general.todos_progress')}</span>
                                                    <span className="text-foreground">{task.progress}% ({task.completed_todos}/{task.total_todos})</span>
                                                </div>
                                                <div className="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                                                    <div
                      className="h-full bg-emerald-500 rounded-full transition-all duration-500"
                      style={{ width: `${task.progress || 0}%` }} />
                    
                                                </div>
                                            </div> :

                <div className="text-muted-foreground text-xs italic flex items-center gap-1">
                                                <Clock className="h-3.5 w-3.5" />{__('general.no_items_added_yet')}</div>
                }

                                        {/* Badges & Meta */}
                                        <div className="flex items-center justify-end gap-4 border-t border-border pt-3 mt-auto">
                                            <div className="me-auto flex items-center gap-2">
                                                {getPriorityBadge(task.priority)}
                                                {getStatusBadge(task.status)}
                                            </div>

                                            {task.due_date &&
                  <div className="flex items-center gap-1 text-[11px] text-muted-foreground font-medium">
                                                    <Calendar className="h-3.5 w-3.5" />
                                                    <span>{task.due_date}</span>
                                                </div>
                  }
                                        </div>
                                    </CardContent>

                                    {/* Action Hover Panel */}
                                    <div className="bg-muted/10 border-t border-border px-5 py-2.5 flex items-center justify-end gap-4 gap-4 text-xs font-medium">
                                        <Link
                  href={route('erp.tasks.show', task.id)}
                  className="me-auto text-primary hover:underline flex items-center gap-0.5">
                  {__('general.manage_board')}<ChevronRight className="h-3.5 w-3.5" />
                                        </Link>

                                        <div className="flex items-center gap-2">
                                            {task.archived ?
                  <Button
                    onClick={() => handleUnarchive(task.id)}
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7 text-zinc-500 hover:text-zinc-700"
                    title={__('general.restore_board')}>
                    
                                                    <Archive className="h-3.5 w-3.5 rotate-180" />
                                                </Button> :

                  <Button
                    onClick={() => handleArchive(task.id)}
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7 text-zinc-500 hover:text-zinc-700"
                    title={__('general.archive_board')}>
                    
                                                    <Archive className="h-3.5 w-3.5" />
                                                </Button>
                  }
                                            <Button
                    onClick={() => handleDelete(task.id)}
                    variant="ghost"
                    size="icon"
                    className="h-7 w-7 text-rose-500 hover:text-rose-700 hover:bg-rose-50"
                    title={__('general.delete_board')}>
                    
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </Card>
                            </motion.div>
          )}
                    </div>
        }
            </div>

            {/* Create Task Board Dialog Modal */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Plus className="h-5 w-5 text-primary" />{__('general.create_task_board')}</DialogTitle>
                        <DialogDescription className="text-xs">{__('general.set_up_a_shared_dedicated_board_where_you_can_add_organize_and_check_off_todo_list_items_with_your_client')}</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateSubmit} className="space-y-4 py-2 text-xs">
                        {/* Name */}
                        <div className="space-y-1.5">
                            <Label htmlFor="task_name" className="text-xs font-semibold text-foreground">{__('general.task_board_title')}</Label>
                            <Input
                id="task_name"
                value={data.task_name}
                onChange={(e) => setData('task_name', e.target.value)}
                placeholder={__('general.e_g_phase_1_ui_design_prototypes')}
                className="shadow-none h-9 text-xs"
                required />
              
                            {errors.task_name && <p className="text-rose-500 text-[11px] font-medium">{errors.task_name}</p>}
                        </div>

                        {/* Description */}
                        <div className="space-y-1.5">
                            <Label htmlFor="task_description" className="text-xs font-semibold text-foreground">{__('general.brief_description')}</Label>
                            <Textarea
                id="task_description"
                value={data.task_description}
                onChange={(e) => setData('task_description', e.target.value)}
                placeholder={__('general.detail_what_this_board_represents_for_your_client')}
                className="shadow-none text-xs min-h-[80px] resize-none" />
              
                            {errors.task_description && <p className="text-rose-500 text-[11px] font-medium">{errors.task_description}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            {/* Client ID */}
                            <div className="space-y-1.5">
                                <Label htmlFor="client_id" className="text-xs font-semibold text-foreground">{__('general.select_client')}</Label>
                                <ClientAutocomplete
                  value={data.client_id}
                  onChange={(val) => {
                    setData((prev) => ({
                      ...prev,
                      client_id: val,
                      project_id: ''
                    }));
                  }}
                  placeholder={__('general.choose_a_client')} />
                
                                {errors.client_id && <p className="text-rose-500 text-[11px] font-medium">{errors.client_id}</p>}
                            </div>

                            {/* Project ID */}
                            <div className="space-y-1.5">
                                <Label htmlFor="project_id" className="text-xs font-semibold text-foreground">{__('general.select_project')}</Label>
                                <select
                  id="project_id"
                  value={data.project_id}
                  onChange={(e) => setData('project_id', e.target.value)}
                  className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring"
                  disabled={!data.client_id}>
                  
                                    <option value="">None (Independent Board)</option>
                                    {projects.
                  filter((p) => String(p.client_id) === String(data.client_id)).
                  map((p) =>
                  <option key={p.id} value={p.id}>{p.name}</option>
                  )}
                                </select>
                                {errors.project_id && <p className="text-rose-500 text-[11px] font-medium">{errors.project_id}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            {/* Priority */}
                            <div className="space-y-1.5">
                                <Label htmlFor="priority" className="text-xs font-semibold text-foreground">{__('general.priority_level')}</Label>
                                <select
                  id="priority"
                  value={data.priority}
                  onChange={(e) => setData('priority', e.target.value)}
                  className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring">
                  
                                    <option value="low">Low</option>
                                    <option value="normal">{__('general.normal')}</option>
                                    <option value="high">{__('general.high')}</option>
                                    <option value="urgent">{__('general.urgent')}</option>
                                </select>
                                {errors.priority && <p className="text-rose-500 text-[11px] font-medium">{errors.priority}</p>}
                            </div>

                            {/* Status */}
                            <div className="space-y-1.5">
                                <Label htmlFor="status" className="text-xs font-semibold text-foreground">{__('general.initial_status')}</Label>
                                <select
                  id="status"
                  value={data.status}
                  onChange={(e) => setData('status', e.target.value)}
                  className="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-none focus:outline-none focus:ring-1 focus:ring-ring">
                  
                                    <option value="open">{__('general.open')}</option>
                                    <option value="in_progress">{__('general.in_progress')}</option>
                                    <option value="review">{__('general.review')}</option>
                                    <option value="completed">{__('general.completed')}</option>
                                </select>
                                {errors.status && <p className="text-rose-500 text-[11px] font-medium">{errors.status}</p>}
                            </div>
                        </div>

                        {/* Due Date */}
                        <div className="space-y-1.5">
                            <Label htmlFor="due_date" className="text-xs font-semibold text-foreground">{__('general.target_due_date')}</Label>
                            <Input
                id="due_date"
                type="date"
                value={data.due_date}
                onChange={(e) => setData('due_date', e.target.value)}
                className="shadow-none h-9 text-xs" />
              
                            {errors.due_date && <p className="text-rose-500 text-[11px] font-medium">{errors.due_date}</p>}
                        </div>

                        <DialogFooter className="pt-4 border-t border-border mt-4">
                            <Button
                type="button"
                variant="outline"
                onClick={() => setIsCreateOpen(false)}
                className="shadow-none text-xs"
                disabled={processing}>
                
                                {__('general.cancel')}</Button>
                            <Button
                type="submit"
                className="shadow-none text-xs gap-2"
                disabled={processing}>
                
                                {processing && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                                Create Board
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </ERPLayout>);

}