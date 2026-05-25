import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Trash2, Edit, Plus, Users, Send, PauseCircle, PlayCircle, Clock } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

export default function Index({ campaigns }) {
    const { auth } = usePage().props as any;
    const [isCreateOpen, setIsCreateOpen] = React.useState(false);
    const [newCampaign, setNewCampaign] = React.useState({ name: '', type: 'email', target_audience: 'all_leads' });

    const handleCreate = (e) => {
        e.preventDefault();
        router.post(route('crm.campaigns.store'), newCampaign, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewCampaign({ name: '', type: 'email', target_audience: 'all_leads' });
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this campaign?')) {
            router.delete(route('crm.campaigns.destroy', id));
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'draft': return 'bg-gray-100 text-gray-800';
            case 'scheduled': return 'bg-blue-100 text-blue-800';
            case 'sending': return 'bg-yellow-100 text-yellow-800';
            case 'paused': return 'bg-orange-100 text-orange-800';
            case 'completed': return 'bg-green-100 text-green-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Broadcast Campaigns</h2>}
        >
            <div className="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
            <div className="mb-6 flex justify-end">
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button><Plus className="w-4 h-4 mr-2" /> Create Campaign</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>Create New Broadcast Campaign</DialogTitle>
                                <DialogDescription>Send a one-time message to a specific audience segment.</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label>Campaign Name</Label>
                                    <Input 
                                        required 
                                        value={newCampaign.name} 
                                        onChange={(e) => setNewCampaign({...newCampaign, name: e.target.value})} 
                                        placeholder="e.g. Summer Sale 2026" 
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Campaign Type</Label>
                                    <select 
                                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                                        value={newCampaign.type}
                                        onChange={(e) => setNewCampaign({...newCampaign, type: e.target.value})}
                                    >
                                        <option value="email">Email Only</option>
                                        <option value="whatsapp">WhatsApp Only</option>
                                        <option value="mixed">Email + WhatsApp</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Target Audience</Label>
                                    <select 
                                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                                        value={newCampaign.target_audience}
                                        onChange={(e) => setNewCampaign({...newCampaign, target_audience: e.target.value})}
                                    >
                                        <option value="all_leads">All Leads</option>
                                        <option value="active_users">Active Users</option>
                                        <option value="subscribers">Newsletter Subscribers</option>
                                    </select>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">Create Campaign</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {campaigns.data.map(campaign => (
                    <div key={campaign.id} className="bg-white rounded-lg shadow border overflow-hidden flex flex-col">
                        <div className="p-5 flex-1">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="font-semibold text-lg text-gray-900 truncate pr-2">{campaign.name}</h3>
                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${getStatusColor(campaign.status)}`}>
                                    {campaign.status}
                                </span>
                            </div>
                            
                            <div className="space-y-3 mb-6">
                                <div className="flex items-center text-sm text-gray-500">
                                    <Send className="w-4 h-4 mr-2 text-indigo-400" />
                                    <span>Type: <span className="font-medium text-gray-700 capitalize">{campaign.type}</span></span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <Users className="w-4 h-4 mr-2 text-blue-400" />
                                    <span>Audience: <span className="font-medium text-gray-700">{campaign.target_audience.replace('_', ' ')}</span></span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <Clock className="w-4 h-4 mr-2 text-gray-400" />
                                    <span>
                                        {campaign.status === 'scheduled' ? `Scheduled: ${new Date(campaign.scheduled_at).toLocaleString()}` : 
                                         campaign.status === 'completed' ? `Finished: ${new Date(campaign.completed_at).toLocaleDateString()}` : 
                                         'Not Scheduled'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-gray-50 border-t p-3 flex justify-between items-center">
                            <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(campaign.id)}>
                                <Trash2 className="w-4 h-4" />
                            </Button>
                            <Link href={route('crm.campaigns.show', campaign.id)}>
                                <Button size="sm" variant="outline">
                                    <Edit className="w-4 h-4 mr-2" /> Manage Content
                                </Button>
                            </Link>
                        </div>
                    </div>
                ))}

                {campaigns.data.length === 0 && (
                    <div className="col-span-full py-12 text-center text-gray-500 bg-white rounded-lg border shadow-sm">
                        <Send className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <h3 className="text-lg font-medium text-gray-900">No Campaigns Found</h3>
                        <p className="mt-1">Create your first broadcast campaign to reach your audience.</p>
                    </div>
                )}
            </div>
            </div>
        </AuthenticatedLayout>
    );
}
