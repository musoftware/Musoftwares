import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from "@/Layouts/AdminLayout";
import { ShieldCheck, Check, X, Eye, FileText, Search, User } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { useToast } from '@/Components/ui/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";

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
        <AdminLayout user={auth?.user}>
            <Head title="KYC Applications" />
            <div className="space-y-6 pb-20 p-6">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold font-sora tracking-tight">KYC Applications</h1>
                        <p className="text-sm text-muted-foreground mt-1">Review and approve identity verification requests.</p>
                    </div>
                </div>

                <Card>
                    <CardHeader className="bg-slate-50/50 border-b border-slate-100">
                        <CardTitle className="text-lg">Pending Reviews</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6 py-4">Client</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Documents</TableHead>
                                    <TableHead>Submitted</TableHead>
                                    <TableHead className="text-right pr-6">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id} className="hover:bg-slate-50/50">
                                        <TableCell className="pl-6 py-4">
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
                                                <Badge variant="secondary" className="bg-amber-100 text-amber-700 hover:bg-amber-100">Review Required</Badge>
                                            ) : user.kyc_status === 'verified' ? (
                                                <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Verified</Badge>
                                            ) : (
                                                <Badge variant="outline">Unverified</Badge>
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
                                                {user.documents.length === 0 && <span className="text-xs text-slate-400">No docs</span>}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-slate-500">
                                            {new Date(user.submitted_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-right pr-6">
                                            <Button variant="outline" size="sm" className="h-8" onClick={() => setSelectedUser(user)}>
                                                Review
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {users.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-slate-500">
                                            <ShieldCheck className="w-8 h-8 mx-auto mb-3 text-slate-300" />
                                            No pending KYC applications.
                                        </TableCell>
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
                                <DialogDescription>Review the documents below to verify this user's identity.</DialogDescription>
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
                                                <Eye className="w-4 h-4 mr-1" /> View
                                            </a>
                                        </Button>
                                    </div>
                                ))}
                                {selectedUser.documents.length === 0 && (
                                    <div className="col-span-2 text-center py-4 text-slate-500 border border-dashed rounded-xl">
                                        No documents uploaded
                                    </div>
                                )}
                            </div>

                            <DialogFooter className="flex gap-2 sm:justify-between border-t pt-4">
                                <Button variant="outline" onClick={() => setSelectedUser(null)}>Cancel</Button>
                                <div className="flex gap-2">
                                    <Button variant="destructive" onClick={() => setIsRejectDialogOpen(true)}>
                                        <X className="w-4 h-4 mr-1" /> Reject
                                    </Button>
                                    <Button className="bg-emerald-600 hover:bg-emerald-700 text-white" onClick={() => handleApprove(selectedUser.id)}>
                                        <Check className="w-4 h-4 mr-1" /> Approve Verification
                                    </Button>
                                </div>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}

                {/* REJECT DIALOG */}
                <Dialog open={isRejectDialogOpen} onOpenChange={setIsRejectDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="text-rose-600">Reject Application</DialogTitle>
                            <DialogDescription>Please provide a reason for rejecting this KYC application. The user will see this message.</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleReject} className="space-y-4">
                            <div className="space-y-2">
                                <Input 
                                    placeholder="e.g. ID photo is blurry, Please upload a valid passport."
                                    value={data.reason}
                                    onChange={e => setData('reason', e.target.value)}
                                    required
                                />
                                {errors.reason && <p className="text-rose-500 text-xs">{errors.reason}</p>}
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setIsRejectDialogOpen(false)}>Cancel</Button>
                                <Button type="submit" variant="destructive" disabled={processing}>Confirm Rejection</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AdminLayout>
    );
}
