import React, { useEffect, useState } from 'react';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Loader2, Mail, Phone, Building, Clock, Activity, Pin, MessageSquare } from 'lucide-react';
import axios from 'axios';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Textarea } from '@/Components/ui/textarea';
import { router } from '@inertiajs/react';

interface LeadSlideOverProps {
    leadId: number | null;
    isOpen: boolean;
    onClose: () => void;
}

export function LeadSlideOver({ leadId, isOpen, onClose }: LeadSlideOverProps) {
    const [loading, setLoading] = useState(false);
    const [lead, setLead] = useState<any>(null);
    const [timeline, setTimeline] = useState<any[]>([]);
    const [newNote, setNewNote] = useState('');
    const [savingNote, setSavingNote] = useState(false);

    useEffect(() => {
        if (isOpen && leadId) {
            fetchLeadData();
        } else {
            setLead(null);
            setTimeline([]);
        }
    }, [isOpen, leadId]);

    const fetchLeadData = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('crm.leads.show', leadId));
            setLead(response.data.lead);
            setTimeline(response.data.timeline);
        } catch (error) {
            console.error("Failed to fetch lead data", error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddNote = () => {
        if (!newNote.trim()) return;
        
        setSavingNote(true);
        router.post(route('crm.leads.notes.store', leadId), {
            note: newNote,
            is_pinned: false
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNewNote('');
                fetchLeadData(); // Refresh timeline
            },
            onFinish: () => setSavingNote(false)
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'converted': return 'bg-green-100 text-green-800 border-green-200';
            case 'contacted': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'dead': return 'bg-gray-100 text-gray-800 border-gray-200';
            default: return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        }
    };

    return (
        <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-[400px] sm:w-[540px] p-0 flex flex-col bg-slate-50">
                {loading || !lead ? (
                    <div className="flex-1 flex items-center justify-center">
                        <Loader2 className="h-8 w-8 animate-spin text-indigo-600" />
                    </div>
                ) : (
                    <>
                        {/* Header Section */}
                        <div className="bg-white border-b border-slate-200 p-6 shadow-sm z-10">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h2 className="text-xl font-semibold text-slate-900">{lead.name}</h2>
                                    <p className="text-sm text-slate-500 mt-1 flex items-center gap-2">
                                        <Building className="h-4 w-4" />
                                        {lead.company || 'No Company'}
                                    </p>
                                </div>
                                <Badge variant="outline" className={getStatusColor(lead.status) + " capitalize px-3 py-1 text-sm font-medium"}>
                                    {lead.status}
                                </Badge>
                            </div>

                            <div className="flex gap-4 text-sm text-slate-600">
                                {lead.email && (
                                    <div className="flex items-center gap-1.5">
                                        <Mail className="h-4 w-4 text-slate-400" />
                                        <a href={`mailto:${lead.email}`} className="hover:text-indigo-600 transition-colors">{lead.email}</a>
                                    </div>
                                )}
                                {lead.phone && (
                                    <div className="flex items-center gap-1.5">
                                        <Phone className="h-4 w-4 text-slate-400" />
                                        <a href={`tel:${lead.phone}`} className="hover:text-indigo-600 transition-colors">{lead.phone}</a>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Quick Actions & Note Input */}
                        <div className="bg-white border-b border-slate-200 p-4">
                            <div className="space-y-3">
                                <Textarea 
                                    placeholder="Add a note to this lead..." 
                                    className="min-h-[80px] resize-none border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 transition-all"
                                    value={newNote}
                                    onChange={(e) => setNewNote(e.target.value)}
                                />
                                <div className="flex justify-between items-center">
                                    <div className="flex gap-2">
                                        {/* Quick Actions could go here */}
                                    </div>
                                    <Button 
                                        size="sm" 
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm"
                                        onClick={handleAddNote}
                                        disabled={savingNote || !newNote.trim()}
                                    >
                                        {savingNote ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <MessageSquare className="h-4 w-4 mr-2" />}
                                        Save Note
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {/* Timeline Area */}
                        <ScrollArea className="flex-1 p-6">
                            <h3 className="text-sm font-semibold text-slate-900 mb-6 flex items-center gap-2">
                                <Activity className="h-4 w-4 text-indigo-500" />
                                Activity Timeline
                            </h3>
                            
                            <div className="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 before:to-transparent">
                                {timeline.map((event, idx) => (
                                    <div key={event.id} className="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                        
                                        {/* Icon */}
                                        <div className="flex items-center justify-center w-10 h-10 rounded-full border-4 border-slate-50 bg-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 text-slate-500">
                                            {event.event === 'note.created' ? <MessageSquare className="h-4 w-4 text-amber-500" /> :
                                             event.event === 'lead.stage_changed' ? <Activity className="h-4 w-4 text-indigo-500" /> :
                                             <Clock className="h-4 w-4" />}
                                        </div>
                                        
                                        {/* Card */}
                                        <div className="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                                            <div className="flex items-center justify-between space-x-2 mb-1">
                                                <div className="font-semibold text-slate-900 text-sm">{event.user?.name || 'System'}</div>
                                                <time className="font-medium text-[11px] text-slate-400">{event.created_at_human}</time>
                                            </div>
                                            <div className="text-slate-600 text-sm">
                                                {event.description}
                                            </div>
                                            {event.event === 'note.created' && (
                                                <div className="mt-2 text-sm text-slate-700 bg-amber-50 p-3 rounded-lg border border-amber-100 relative">
                                                    {event.is_pinned && <Pin className="absolute top-2 right-2 h-3 w-3 text-amber-600 rotate-45" />}
                                                    {event.content}
                                                </div>
                                            )}
                                            {event.event === 'lead.stage_changed' && (
                                                <div className="mt-2 flex items-center gap-2 text-xs font-medium">
                                                    <Badge variant="outline" className="bg-slate-50 text-slate-500 capitalize">{event.old_value}</Badge>
                                                    <span className="text-slate-400">→</span>
                                                    <Badge variant="outline" className={getStatusColor(event.new_value) + " capitalize"}>{event.new_value}</Badge>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                
                                {timeline.length === 0 && (
                                    <div className="text-center text-slate-500 py-10 bg-white rounded-xl border border-slate-200 border-dashed">
                                        No activity recorded yet.
                                    </div>
                                )}
                            </div>
                        </ScrollArea>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
