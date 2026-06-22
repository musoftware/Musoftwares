import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminSidebarLayout from "@/Layouts/AdminSidebarLayout";
import { ShieldCheck, Check, X, Eye, FileText, Search, User } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { useToast } from '@/Components/ui/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { __ } from '@/lib/i18n';

export default function AdminKycIndex({ auth, users }) {
    const { toast } = useToast();
    const [selectedUser, setSelectedUser] = useState(null);
    const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);
    
    const { data, setData, post, processing, reset, errors } = useForm({
        reason: ''
    });

    const handleApprove = (userId) => {
        if (confirm("Are you sure you want to approve this KYC application? This will grant the user full financial capabilities.")) {
            router.post(route('admin.kyc.approve', userId), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast({ title: "Approved", description: "User KYC verified successfully." });
                    setSelectedUser(null);
                }
            });
        }
    };

    const handleReject = (e) => {
        e.preventDefault();
        post(route('admin.kyc.reject', selectedUser.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: "Rejected", description: "Application rejected.", variant: "destructive" });
                setIsRejectDialogOpen(false);
                setSelectedUser(null);
                reset();
            }
        });
    };

    return (
        <AdminSidebarLayout user={auth?.user} title={__('general.kyc_applications')} header="KYC Applications">
            <div className="space-y-6 pb-20 p-6">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold font-sora tracking-tight">{__('general.kyc_applications')}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{__('general.review_and_approve_identity_verification_requests')}</p>
                    </div>
                </div>

                <Card>
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                        <CardTitle className="text-lg">{__('general.pending_reviews')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="ps-6 py-4">{__('general.client')}</TableHead>
                                    <TableHead>{__('general.status')}</TableHead>
                                    <TableHead>{__('general.documents')}</TableHead>
                                    <TableHead>{__('general.submitted')}</TableHead>
                                    <TableHead className="text-end pe-6">{__('general.action')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id} className="hover:bg-slate-50/50">
                                        <TableCell className="ps-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-9 w-9">
                                                    <AvatarFallback className="bg-indigo-100 text-indigo-700 font-bold font-jetbrains">
                                                        {user.name.substring(0, 2).toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <div className="font-medium text-slate-900">{user.name}</div>
                                                    <div className="text-xs text-slate-500 font-mono">{user.email}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {user.kyc_status === 'pending_review' ? (
                                                <Badge variant="secondary" className="bg-amber-100 text-amber-700 hover:bg-amber-100">{__('general.review_required')}</Badge>
                                            ) : user.kyc_status === 'verified' ? (
                                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">{__('general.verified')}</Badge>
                                            ) : (
                                                <Badge variant="outline">{__('general.unverified')}</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-2">
                                                {user.documents.map(doc => (
                                                    <Badge key={doc.id} variant="outline" className="text-[10px] font-mono flex items-center gap-1 bg-white">
                                                        <FileText className="w-3 h-3 text-indigo-500"/>
                                                        {doc.type}
                                                    </Badge>
                                                ))}
                                                {user.documents.length === 0 && <span className="text-xs text-slate-400">{__('general.no_docs')}</span>}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-slate-500">
                                            {new Date(user.submitted_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-end pe-6">
                                            <Button variant="outline" size="sm" className="h-8" onClick={() => setSelectedUser(user)}>
                                                {__('general.review')}</Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {users.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-slate-500">
                                            <ShieldCheck className="w-8 h-8 mx-auto mb-3 text-slate-300" />{__('general.no_pending_kyc_applications')}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* REVIEW DIALOG */}
                {selectedUser && (
                    <Dialog open={!!selectedUser} onOpenChange={(open) => !open && setSelectedUser(null)}>
                        <DialogContent className="max-w-3xl">
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <User className="w-5 h-5 text-indigo-600" /> Review Application: {selectedUser.name}
                                </DialogTitle>
                                <DialogDescription>{__('general.review_the_documents_below_to_verify_this_user_s_identity')}</DialogDescription>
                            </DialogHeader>

                            <div className="grid grid-cols-2 gap-4 my-4">
                                {selectedUser.documents.map(doc => (
                                    <div key={doc.id} className="border border-slate-200 rounded-xl p-4 bg-slate-50 flex justify-between items-center">
                                        <div className="flex items-center gap-3 overflow-hidden">
                                            <div className="w-10 h-10 rounded bg-white border border-slate-200 flex items-center justify-center flex-shrink-0">
                                                <FileText className="w-5 h-5 text-indigo-500" />
                                            </div>
                                            <div className="truncate">
                                                <h4 className="text-sm font-semibold uppercase tracking-wider">{doc.type.replace('_', ' ')}</h4>
                                                <p className="text-xs text-slate-500 truncate">{doc.filename}</p>
                                            </div>
                                        </div>
                                        <Button variant="secondary" size="sm" asChild>
                                            <a href={route('kyc.download', doc.id)} target="_blank" rel="noreferrer">
                                                <Eye className="w-4 h-4 me-1" /> {__('general.view')}</a>
                                        </Button>
                                    </div>
                                ))}
                                {selectedUser.documents.length === 0 && (
                                    <div className="col-span-2 text-center py-4 text-slate-500 border border-dashed rounded-xl">{__('general.no_documents_uploaded')}</div>
                                )}
                            </div>

                            <DialogFooter className="flex gap-2 sm:justify-between border-t pt-4">
                                <Button variant="outline" onClick={() => setSelectedUser(null)}>{__('general.cancel')}</Button>
                                <div className="flex gap-2">
                                    <Button variant="destructive" onClick={() => setIsRejectDialogOpen(true)}>
                                        <X className="w-4 h-4 me-1" /> {__('general.reject')}</Button>
                                    <Button className="bg-emerald-600 hover:bg-emerald-700 text-white" onClick={() => handleApprove(selectedUser.id)}>
                                        <Check className="w-4 h-4 me-1" />{__('general.approve_verification')}</Button>
                                </div>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}

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
                                <Button type="button" variant="outline" onClick={() => setIsRejectDialogOpen(false)}>{__('general.cancel')}</Button>
                                <Button type="submit" variant="destructive" disabled={processing}>{__('general.confirm_rejection')}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AdminSidebarLayout>
    );
}
