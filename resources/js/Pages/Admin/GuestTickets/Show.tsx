import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, MessageCircle } from 'lucide-react';

export default function Show({ ticket }: { ticket: any }) {
    return (
        <AdminSidebarLayout 
            header={`Ticket #${ticket.id}`}
            actions={
                <Link href={route('admin.guest-tickets.index')}>
                    <Button variant="outline"><ArrowLeft className="w-4 h-4 mr-2" /> Back</Button>
                </Link>
            }
        >
            <Head title={`Ticket #${ticket.id}`} />
            <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-3xl mx-auto space-y-6">
                <div className="grid grid-cols-2 gap-4 border-b border-slate-100 pb-6">
                    <div>
                        <p className="text-sm text-slate-500">Name</p>
                        <p className="font-medium">{ticket.name}</p>
                    </div>
                    <div>
                        <p className="text-sm text-slate-500">Email</p>
                        <p className="font-medium">{ticket.email}</p>
                    </div>
                    <div>
                        <p className="text-sm text-slate-500">Mobile</p>
                        <p className="font-medium flex items-center gap-2">
                            {ticket.mobile} 
                            <a href={`https://wa.me/${ticket.mobile.replace(/\D/g, '')}`} target="_blank" rel="noreferrer" className="text-[#25D366] hover:opacity-80">
                                <MessageCircle className="w-4 h-4" />
                            </a>
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-slate-500">Date</p>
                        <p className="font-medium">{new Date(ticket.created_at).toLocaleString()}</p>
                    </div>
                </div>
                
                <div>
                    <p className="text-sm text-slate-500 mb-2">Message</p>
                    <div className="bg-slate-50 rounded-lg p-4 text-slate-700 whitespace-pre-wrap border border-slate-100">
                        {ticket.body}
                    </div>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
