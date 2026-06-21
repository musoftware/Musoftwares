import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { Loader2, CheckCircle2, XCircle, X } from 'lucide-react';
import { Button } from '@/Components/ui/button';

interface Task {
    id: number;
    type: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    progress: number;
    result?: any;
    error?: string;
}

export default function BackgroundTaskStatus() {
    const user = usePage().props.auth.user;
    const [tasks, setTasks] = useState<Task[]>([]);
    const [isOpen, setIsOpen] = useState(true);

    useEffect(() => {
        if (!user) return;

        // Fetch active tasks
        axios.get('/api/background-tasks')
            .then(res => {
                const activeTasks = res.data.data.filter((t: Task) => t.status !== 'completed' && t.status !== 'failed');
                setTasks(activeTasks);
            })
            .catch(err => console.error("Failed to fetch background tasks", err));

        // Listen for Echo events
        if (window.Echo) {
            window.Echo.private(`user.${user.id}`)
                .listen('.BackgroundTaskUpdated', (e: Task) => {
                    setTasks(prev => {
                        const exists = prev.find(t => t.id === e.id);
                        if (exists) {
                            return prev.map(t => t.id === e.id ? e : t);
                        }
                        return [e, ...prev];
                    });
                });
        }

        return () => {
            if (window.Echo && user) {
                window.Echo.leave(`user.${user.id}`);
            }
        };
    }, [user]);

    const dismissTask = (id: number) => {
        setTasks(prev => prev.filter(t => t.id !== id));
    };

    if (!user || tasks.length === 0 || !isOpen) return null;

    return (
        <div className="fixed bottom-4 end-4 z-50 flex flex-col gap-2 max-w-sm w-full">
            {tasks.map(task => (
                <div key={task.id} className="bg-background border shadow-lg rounded-xl p-4 flex flex-col gap-3 relative animate-in slide-in-from-bottom-5">
                    <Button 
                        variant="ghost" 
                        size="icon" 
                        className="absolute top-2 end-2 h-6 w-6 text-muted-foreground hover:text-foreground"
                        onClick={() => dismissTask(task.id)}
                    >
                        <X className="h-4 w-4" />
                    </Button>
                    
                    <div className="flex items-center gap-3">
                        {task.status === 'pending' || task.status === 'processing' ? (
                            <Loader2 className="h-5 w-5 animate-spin text-primary" />
                        ) : task.status === 'completed' ? (
                            <CheckCircle2 className="h-5 w-5 text-emerald-500" />
                        ) : (
                            <XCircle className="h-5 w-5 text-destructive" />
                        )}
                        <div className="flex-1">
                            <h4 className="text-sm font-semibold capitalize">{task.type.replace(/_/g, ' ')}</h4>
                            <p className="text-xs text-muted-foreground capitalize">{task.status}</p>
                        </div>
                    </div>

                    {(task.status === 'pending' || task.status === 'processing') && (
                        <div className="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                            <div 
                                className="bg-primary h-full transition-all duration-300"
                                style={{ width: `${task.progress}%` }}
                            />
                        </div>
                    )}

                    {task.status === 'failed' && task.error && (
                        <p className="text-xs text-destructive bg-destructive/10 p-2 rounded-md">
                            {task.error}
                        </p>
                    )}
                </div>
            ))}
        </div>
    );
}
