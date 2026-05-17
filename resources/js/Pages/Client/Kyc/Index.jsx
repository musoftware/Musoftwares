import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { ShieldCheck, UploadCloud, FileText, CheckCircle2, AlertCircle, XCircle, Trash2, Download } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { useToast } from '@/Components/ui/use-toast';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function KycIndex({ auth, kycStatus, documents, missingDocs, requiredDocs }) {
    const { toast } = useToast();
    const [uploadingType, setUploadingType] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        document_type: '',
        document: null,
    });

    const handleFileChange = (e, type) => {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                toast({ title: "File too large", description: "Maximum file size is 5MB.", variant: "destructive" });
                return;
            }
            setData({ document_type: type, document: file });
            setUploadingType(type);
        }
    };

    const handleUpload = (e) => {
        e.preventDefault();
        post(route('kyc.upload'), {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: "Uploaded", description: "Document uploaded successfully." });
                reset();
                setUploadingType(null);
            },
            onError: () => {
                setUploadingType(null);
            }
        });
    };

    const handleSubmitForReview = () => {
        router.post(route('kyc.submit'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: "Submitted", description: "Your KYC application is now under review." });
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm("Are you sure you want to delete this document?")) {
            router.delete(route('kyc.delete', id), {
                preserveScroll: true,
                onSuccess: () => toast({ title: "Deleted", description: "Document removed." })
            });
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'approved': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'rejected': return 'bg-rose-50 text-rose-700 border-rose-200';
            default: return 'bg-amber-50 text-amber-700 border-amber-200';
        }
    };

    const isVerified = kycStatus.isVerified;
    const isPendingReview = !isVerified && kycStatus.notes?.includes('submitted');
    const isRejected = !isVerified && kycStatus.notes?.includes('rejected');
    const canSubmit = missingDocs.length === 0 && !isVerified && !isPendingReview;

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Identity Verification (KYC)</h2>}>
            <Head title="KYC Verification" />

            <div className="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
                {/* STATUS BANNER */}
                <div className={`p-6 rounded-2xl border ${
                    isVerified ? 'bg-emerald-50 border-emerald-200' :
                    isPendingReview ? 'bg-amber-50 border-amber-200' :
                    isRejected ? 'bg-rose-50 border-rose-200' :
                    'bg-slate-50 border-slate-200'
                }`}>
                    <div className="flex items-start gap-4">
                        <div className={`p-3 rounded-full ${
                            isVerified ? 'bg-emerald-100 text-emerald-600' :
                            isPendingReview ? 'bg-amber-100 text-amber-600' :
                            isRejected ? 'bg-rose-100 text-rose-600' :
                            'bg-slate-200 text-slate-600'
                        }`}>
                            {isVerified ? <CheckCircle2 className="w-6 h-6" /> :
                             isPendingReview ? <AlertCircle className="w-6 h-6" /> :
                             isRejected ? <XCircle className="w-6 h-6" /> :
                             <ShieldCheck className="w-6 h-6" />}
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900 mb-1">
                                {isVerified ? 'Identity Verified' :
                                 isPendingReview ? 'Application Under Review' :
                                 isRejected ? 'Application Rejected' :
                                 'Verification Required'}
                            </h3>
                            <p className="text-sm text-slate-600">
                                {isVerified ? 'Your identity has been fully verified. You have full access to all financial features.' :
                                 isPendingReview ? 'We are reviewing your documents. This usually takes 1-2 business days.' :
                                 isRejected ? `Your application was rejected. Reason: ${kycStatus.notes?.replace('KYC rejected: ', '')}` :
                                 'To comply with financial regulations and enable withdrawals, please verify your identity.'}
                            </p>
                        </div>
                    </div>
                </div>

                {!isVerified && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* REQUIRED DOCUMENTS */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2"><UploadCloud className="w-5 h-5" /> Upload Documents</CardTitle>
                                <CardDescription>Please provide clear, readable photos of the following documents.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {[
                                    { type: 'id_front', label: 'Government ID (Front)', desc: 'Passport, Driver License, or National ID' },
                                    { type: 'selfie', label: 'Selfie with ID', desc: 'A clear photo of your face holding your ID' },
                                    { type: 'proof_of_address', label: 'Proof of Address (Optional)', desc: 'Utility bill or bank statement (Last 3 months)' }
                                ].map((docDef) => {
                                    const uploadedDoc = documents.find(d => d.document_type === docDef.type);
                                    
                                    return (
                                        <div key={docDef.type} className="border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                                            <div className="flex justify-between items-start mb-3">
                                                <div>
                                                    <h4 className="font-semibold text-sm text-slate-900 flex items-center gap-2">
                                                        {docDef.label}
                                                        {requiredDocs.includes(docDef.type) && <span className="text-rose-500 text-[10px] uppercase font-bold px-1.5 py-0.5 bg-rose-100 rounded-md">Required</span>}
                                                    </h4>
                                                    <p className="text-xs text-slate-500 mt-1">{docDef.desc}</p>
                                                </div>
                                                {uploadedDoc && (
                                                    <Badge variant="outline" className={getStatusColor(uploadedDoc.status)}>
                                                        {uploadedDoc.status}
                                                    </Badge>
                                                )}
                                            </div>

                                            {uploadedDoc ? (
                                                <div className="flex items-center justify-between bg-white border border-slate-200 p-2.5 rounded-lg">
                                                    <div className="flex items-center gap-2 overflow-hidden">
                                                        <FileText className="w-4 h-4 text-indigo-500 flex-shrink-0" />
                                                        <span className="text-xs font-medium text-slate-700 truncate">{uploadedDoc.original_filename}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Button variant="ghost" size="icon" className="h-7 w-7 text-slate-400 hover:text-indigo-600" asChild>
                                                            <a href={route('kyc.download', uploadedDoc.id)} target="_blank" rel="noreferrer">
                                                                <Download className="w-3.5 h-3.5" />
                                                            </a>
                                                        </Button>
                                                        {uploadedDoc.status !== 'approved' && !isPendingReview && (
                                                            <Button variant="ghost" size="icon" className="h-7 w-7 text-slate-400 hover:text-rose-600" onClick={() => handleDelete(uploadedDoc.id)}>
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            ) : (
                                                !isPendingReview && (
                                                    <form onSubmit={handleUpload} className="flex gap-2 items-center">
                                                        <Input 
                                                            type="file" 
                                                            className="text-xs file:text-xs h-9 cursor-pointer" 
                                                            onChange={(e) => handleFileChange(e, docDef.type)}
                                                            accept=".jpg,.jpeg,.png,.pdf"
                                                        />
                                                        {data.document && data.document_type === docDef.type && (
                                                            <Button type="submit" size="sm" className="h-9" disabled={processing}>
                                                                Upload
                                                            </Button>
                                                        )}
                                                    </form>
                                                )
                                            )}
                                            {errors.document && uploadingType === docDef.type && (
                                                <p className="text-rose-500 text-xs mt-2">{errors.document}</p>
                                            )}
                                        </div>
                                    );
                                })}
                            </CardContent>
                            <CardFooter className="bg-slate-50 border-t border-slate-100 flex justify-between p-6 rounded-b-xl">
                                <div className="text-xs text-slate-500">
                                    {missingDocs.length > 0 ? (
                                        <span className="text-amber-600 flex items-center gap-1"><AlertCircle className="w-3.5 h-3.5"/> Missing required documents</span>
                                    ) : (
                                        <span className="text-emerald-600 flex items-center gap-1"><CheckCircle2 className="w-3.5 h-3.5"/> All required documents uploaded</span>
                                    )}
                                </div>
                                <Button 
                                    onClick={handleSubmitForReview} 
                                    disabled={!canSubmit}
                                    className={canSubmit ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : ''}
                                >
                                    Submit Application
                                </Button>
                            </CardFooter>
                        </Card>

                        {/* INFO / GUIDELINES */}
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base font-semibold">Verification Guidelines</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 text-sm text-slate-600">
                                    <div className="flex items-start gap-3">
                                        <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 text-slate-500 font-bold text-xs mt-0.5">1</div>
                                        <p>Ensure documents are well-lit, fully visible, and not cut off at the edges.</p>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 text-slate-500 font-bold text-xs mt-0.5">2</div>
                                        <p>Accepted formats: JPG, PNG, PDF. Maximum file size: 5MB per document.</p>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 text-slate-500 font-bold text-xs mt-0.5">3</div>
                                        <p>For the selfie, hold your ID next to your face so both are clearly readable.</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="bg-indigo-50/50 border border-indigo-100 p-5 rounded-xl space-y-2">
                                <h4 className="font-semibold text-indigo-900 text-sm flex items-center gap-2"><ShieldCheck className="w-4 h-4 text-indigo-500"/> Bank-Grade Security</h4>
                                <p className="text-xs text-indigo-700/80 leading-relaxed">
                                    Your documents are encrypted and stored securely. They are only used for identity verification in compliance with anti-money laundering (AML) regulations and are never shared with third parties.
                                </p>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
