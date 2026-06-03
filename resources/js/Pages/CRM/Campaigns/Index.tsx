import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import CrmLayout from '@/Layouts/CrmLayout';
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
import { __ } from '@/lib/i18n';

interface Campaign {
    id: number;
    name: string;
    type: string;
    status: string;
    target_audience: string;
    scheduled_at: string | null;
    completed_at: string | null;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    campaigns: PaginatedData<Campaign>;
}

export default function Index({ campaigns }: Props) {
    const { auth } = usePage().props as any;
    const [isCreateOpen, setIsCreateOpen] = React.useState(false);
    const [newCampaign, setNewCampaign] = React.useState({ name: '', type: 'email', target_audience: 'all_leads' });

    const handleCreate = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        router.post(route('crm.campaigns.store'), newCampaign, {
            onSuccess: () => {
                setIsCreateOpen(false);
                setNewCampaign({ name: '', type: 'email', target_audience: 'all_leads' });
            }
        });
    };

    const handleDelete = (id: number) => {
        if (confirm(__('general.are_you_sure_you_want_to_delete_this_campaign'))) {
            router.delete(route('crm.campaigns.destroy', id));
        }
    };

    const getStatusColor = (status: string) => {
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
        <CrmLayout title={__('general.broadcast_campaigns')} activeMenu="campaigns">
            <div className="flex-1 space-y-4 p-8 pt-6">
            <div className="mb-6 flex justify-end">
                <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                    <DialogTrigger asChild>
                        <Button><Plus className="w-4 h-4 mr-2" /> {__('Create Campaign')}</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={handleCreate}>
                            <DialogHeader>
                                <DialogTitle>{__('general.create_new_broadcast_campaign')}</DialogTitle>
                                <DialogDescription>{__('general.send_a_one_time_message_to_a_specific_audience_segment')}</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div className="space-y-2">
                                    <Label>{__('Campaign Name')}</Label>
                                    <Input 
                                        required 
                                        value={newCampaign.name} 
                                        onChange={(e) => setNewCampaign({...newCampaign, name: e.target.value})} 
                                        placeholder={__('general.e_g_summer_sale_2026')} 
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('general.campaign_type')}</Label>
                                    <select 
                                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                                        value={newCampaign.type}
                                        onChange={(e) => setNewCampaign({...newCampaign, type: e.target.value})}
                                    >
                                        <option value="email">{__('Email')}</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>{__('general.target_audience')}</Label>
                                    <select 
                                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                                        value={newCampaign.target_audience}
                                        onChange={(e) => setNewCampaign({...newCampaign, target_audience: e.target.value})}
                                    >
                                        <option value="all_leads">{__('general.all_leads')}</option>
                                        <option value="active_users">{__('general.active_users')}</option>
                                        <option value="subscribers">{__('general.newsletter_subscribers')}</option>
                                    </select>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">{__('Create Campaign')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {(campaigns.data as any).map((campaign: Campaign) => (
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
                                    <span>{__('general.type')} <span className="font-medium text-gray-700 capitalize">{campaign.type}</span></span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <Users className="w-4 h-4 mr-2 text-blue-400" />
                                    <span>{__('general.audience')} <span className="font-medium text-gray-700">{campaign.target_audience.replace('_', ' ')}</span></span>
                                </div>
                                <div className="flex items-center text-sm text-gray-500">
                                    <Clock className="w-4 h-4 mr-2 text-gray-400" />
                                    <span>
                                        {campaign.status === 'scheduled' ? `${__('general.scheduled')}: ${new Date(campaign.scheduled_at!).toLocaleString()}` : 
                                         campaign.status === 'completed' ? `${__('general.finished')}: ${new Date(campaign.completed_at!).toLocaleDateString()}` : 
                                         __('general.not_scheduled')}
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
                                    <Edit className="w-4 h-4 mr-2" /> {__('general.manage_content')}
                                </Button>
                            </Link>
                        </div>
                    </div>
                ))}

                {(campaigns.data as any).length === 0 && (
                    <div className="col-span-full py-12 text-center text-gray-500 bg-white rounded-lg border shadow-sm">
                        <Send className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <h3 className="text-lg font-medium text-gray-900">{__('general.no_campaigns_found')}</h3>
                        <p className="mt-1">{__('general.create_your_first_broadcast_campaign_to_reach_your_audience')}</p>
                    </div>
                )}
            </div>
            </div>
        </CrmLayout>
    );
}
