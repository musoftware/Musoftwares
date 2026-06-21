import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from "@/Layouts/AdminSidebarLayout";
import { ShieldCheck, Check, X, Eye, FileText, ArrowLeft, User } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { useToast } from '@/Components/ui/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";

export default function AdminKycUserDocuments({ auth, user }) {
    const { toast } = useToast();
    const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);
    
    const { data, setData, post, processing, reset, errors } = useForm({
        reason: ''
    });

    const handleApprove = () => {
        if (confirm("Are you sure you want to approve this KYC application? This will grant the user full financial capabilities.")) {
            router.post(route('admin.kyc.approve', user.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast({ title: "Approved", description: "User KYC verified successfully." });
                }
            });
        }
    };

    const handleReject = (e) => {
        e.preventDefault();
        post(route('admin.kyc.reject', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: "Rejected", description: "Application rejected.", variant: "destructive" });
                setIsRejectDialogOpen(false);
                reset();
            }
        });
    };

    return (
        <AdminSidebarLayout user={auth?.user} title={`KYC Documents - ${user.name}`} header="KYC Documents">
            <div className="space-y-6 pb-20 p-6">
                <div className="flex items-center gap-4 mb-6">
                    <Button variant="outline" size="icon" asChild>
                        <Link href={route('admin.users.edit', user.id)}>
                            <ArrowLeft className="w-4 h-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-3xl font-bold font-sora tracking-tight">KYC Documents</h1>
                        <p className="text-sm text-muted-foreground mt-1">Reviewing documents for {user.name}</p>
                    </div>
                </div>

                <Card className="max-w-4xl">
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <User className="w-5 h-5 text-indigo-600" /> {user.name}
                            </CardTitle>
                            <CardDescription className="mt-1">{user.email}</CardDescription>
                        </div>
                        <div>
                            {user.kyc_status === 'pending_review' ? (
                                <Badge variant="secondary" className="bg-amber-100 text-amber-700 hover:bg-amber-100 px-3 py-1 text-sm">{__('general.review_required')}</Badge>
                            ) : user.kyc_status === 'verified' ? (
                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100 px-3 py-1 text-sm">Verified</Badge>
                            ) : (
                                <Badge variant="outline" className="px-3 py-1 text-sm">Unverified</Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {user.documents.map(doc => (
                                <div key={doc.id} className="border border-slate-200 rounded-xl p-4 bg-slate-50 flex justify-between items-center relative overflow-hidden">
                                    <div className="flex items-center gap-3 overflow-hidden">
                                        <div className="w-10 h-10 rounded bg-white border border-slate-200 flex items-center justify-center flex-shrink-0">
                                            <FileText className="w-5 h-5 text-indigo-500" />
                                        </div>
                                        <div className="truncate pe-4">
                                            <h4 className="text-sm font-semibold uppercase tracking-wider">{doc.type.replace('_', ' ')}</h4>
                                            <p className="text-xs text-slate-500 truncate">{doc.filename}</p>
                                        </div>
                                    </div>
                                    <Button variant="secondary" size="sm" asChild>
                                        <a href={route('kyc.download', doc.id)} target="_blank" rel="noreferrer">
                                            <Eye className="w-4 h-4 me-1" /> View
                                        </a>
                                    </Button>
                                    
                                    {doc.status === 'rejected' && (
                                        <div className="absolute top-0 end-0 px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-bl-lg">
                                            REJECTED
                                        </div>
                                    )}
                                    {doc.status === 'approved' && (
                                        <div className="absolute top-0 end-0 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-bl-lg">
                                            APPROVED
                                        </div>
                                    )}
                                </div>
                            ))}
                            {user.documents.length === 0 && (
                                <div className="col-span-1 sm:col-span-2 text-center py-12 text-slate-500 border border-dashed rounded-xl">
                                    <ShieldCheck className="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    {__('general.no_documents_uploaded')}
                                </div>
                            )}
                        </div>

                        {user.kyc_status === 'pending_review' && (
                            <div className="flex gap-2 sm:justify-end border-t mt-8 pt-6">
                                <Button variant="destructive" onClick={() => setIsRejectDialogOpen(true)}>
                                    <X className="w-4 h-4 me-1" /> Reject
                                </Button>
                                <Button className="bg-emerald-600 hover:bg-emerald-700 text-white" onClick={handleApprove}>
                                    <Check className="w-4 h-4 me-1" /> {__('general.approve_verification')}
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* REJECT DIALOG */}
                <Dialog open={isRejectDialogOpen} onOpenChange={setIsRejectDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-rose-600">{__('general.reject_application')}</DialogTitle>
                            <DialogDescription>{__('general.please_provide_a_reason_for_rejecting_this_kyc_application_the_user_will_see_this_message')}</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleReject} className="space-y-4">
                            <div className="space-y-2">
                                <Input 
                                    placeholder={__('general.e_g_id_photo_is_blurry_please_upload_a_valid_passport')}
                                    value={data.reason}
                                    onChange={e => setData('reason', e.target.value)}
                                    required
                                />
                                {errors.reason && <p className="text-rose-500 text-xs">{errors.reason}</p>}
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setIsRejectDialogOpen(false)}>Cancel</Button>
                                <Button type="submit" variant="destructive" disabled={processing}>{__('general.confirm_rejection')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AdminSidebarLayout>
    );
}
