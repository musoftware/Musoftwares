import React, { useState, useCallback } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { ShieldCheck, UploadCloud, FileText, CheckCircle2, AlertCircle, XCircle, Trash2, Download, ArrowRight, ArrowLeft, Image as ImageIcon } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/Components/ui/card';
import { useToast } from '@/Components/ui/use-toast';
import { Badge } from '@/Components/ui/badge';
import { useDropzone } from 'react-dropzone';
import { motion, AnimatePresence } from 'framer-motion';
import { __ } from '@/lib/i18n';

interface DocumentType {
    id: number;
    document_type: string;
    original_filename: string;
    file_size: number;
    status: string;
    rejection_reason?: string;
    created_at: string;
}

interface KycStatus {
    isVerified: boolean;
    verifiedAt: string | null;
    provider: string | null;
    notes: string | null;
}

interface Props {
    auth: { user: any };
    kycStatus: KycStatus;
    documents: DocumentType[];
    missingDocs: string[];
    requiredDocs: string[];
}

const STEPS = [
    { id: 'id_front', label: 'Government ID', desc: 'Front of your Passport or National ID', required: true },
    { id: 'selfie', label: 'Selfie Verification', desc: 'A clear photo of your face holding your ID', required: true },
    { id: 'proof_of_address', label: 'Proof of Address', desc: 'Utility bill or bank statement (Optional)', required: false }
];

export default function KycIndex({ auth, kycStatus, documents, missingDocs, requiredDocs }: Props) {
    const { toast } = useToast();
    const [activeStep, setActiveStep] = useState(0);
    const [uploadingType, setUploadingType] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        document_type: '',
        document: null as File | null,
    });

    const isVerified = kycStatus.isVerified;
    const isPendingReview = !isVerified && kycStatus.notes?.includes('submitted');
    const isRejected = !isVerified && kycStatus.notes?.includes('rejected');
    const canSubmit = missingDocs.length === 0 && !isVerified && !isPendingReview;

    const onDrop = useCallback((acceptedFiles: File[], docType: string) => {
        const file = acceptedFiles[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                toast({ title: "File too large", description: "Maximum file size is 5MB.", variant: "destructive" });
                return;
            }
            setData({ document_type: docType, document: file });
            setUploadingType(docType);
        }
    }, [setData, toast]);

    const handleUpload = (docType: string) => {
        post(route('kyc.upload'), {
            preserveScroll: true,
            onSuccess: () => {
                toast({ title: "Uploaded", description: "Document uploaded successfully." });
                reset();
                setUploadingType(null);
                if (activeStep < STEPS.length - 1) {
                    setActiveStep(prev => prev + 1);
                }
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

    const handleDelete = (id: number) => {
        if (confirm("Are you sure you want to delete this document?")) {
            router.delete(route('kyc.delete', id), {
                preserveScroll: true,
                onSuccess: () => toast({ title: "Deleted", description: "Document removed." })
            });
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'rejected': return 'bg-rose-50 text-rose-700 border-rose-200';
            default: return 'bg-amber-50 text-amber-700 border-amber-200';
        }
    };

    const renderDropzone = (docDef: any) => {
        const { getRootProps, getInputProps, isDragActive } = useDropzone({
            onDrop: (files) => onDrop(files, docDef.id),
            accept: {
                'image/jpeg': [],
                'image/png': [],
                'application/pdf': []
            },
            maxSize: 5242880, // 5MB
            multiple: false
        });

        const uploadedDoc = documents.find(d => d.document_type === docDef.id);
        const isCurrentUploading = data.document && data.document_type === docDef.id;

        if (uploadedDoc) {
            return (
                <div className="flex flex-col items-center justify-center p-8 bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl">
                    <CheckCircle2 className="w-12 h-12 text-emerald-500 mb-3" />
                    <h4 className="text-sm font-semibold text-slate-900">{uploadedDoc.original_filename}</h4>
                    <p className="text-xs text-slate-500 mt-1 mb-4">Uploaded successfully</p>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={route('kyc.download', uploadedDoc.id)} target="_blank" rel="noreferrer">
                                <Download className="w-4 h-4 mr-2" /> View
                            </a>
                        </Button>
                        {!isPendingReview && uploadedDoc.status !== 'approved' && (
                            <Button variant="destructive" size="sm" onClick={() => handleDelete(uploadedDoc.id)}>
                                <Trash2 className="w-4 h-4 mr-2" /> Remove
                            </Button>
                        )}
                    </div>
                </div>
            );
        }

        return (
            <div className="space-y-4">
                <div 
                    {...getRootProps()} 
                    className={`flex flex-col items-center justify-center p-8 border-2 border-dashed rounded-xl cursor-pointer transition-colors ${
                        isDragActive ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100 hover:border-slate-400'
                    }`}
                >
                    <input {...getInputProps()} />
                    <div className="w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4">
                        <UploadCloud className="w-6 h-6" />
                    </div>
                    {isCurrentUploading ? (
                        <div className="text-center">
                            <p className="text-sm font-semibold text-slate-900">{data.document?.name}</p>
                            <p className="text-xs text-slate-500 mt-1">Ready to upload</p>
                        </div>
                    ) : (
                        <div className="text-center">
                            <p className="text-sm font-semibold text-slate-900">Drag & drop your file here</p>
                            <p className="text-xs text-slate-500 mt-1">or click to browse from your device</p>
                            <p className="text-xs text-slate-400 mt-2">JPG, PNG, PDF up to 5MB</p>
                        </div>
                    )}
                </div>
                
                {isCurrentUploading && (
                    <div className="flex justify-end">
                        <Button 
                            onClick={() => handleUpload(docDef.id)} 
                            disabled={processing}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-md"
                        >
                            {processing ? 'Uploading...' : 'Confirm Upload'}
                        </Button>
                    </div>
                )}
                {errors.document && uploadingType === docDef.id && (
                    <p className="text-rose-500 text-xs mt-2">{errors.document}</p>
                )}
            </div>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-foreground leading-tight">Identity Verification</h2>}>
            <Head title={__('general.kyc_verification')} />

            <div className="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
                {/* STATUS BANNER */}
                <div className={`p-6 rounded-2xl border shadow-sm ${
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
                                {isVerified ? 'Your identity has been fully verified. You have full access to all features.' :
                                 isPendingReview ? 'We are reviewing your documents. This usually takes 1-2 business days.' :
                                 isRejected ? `Your application was rejected. Reason: ${kycStatus.notes?.replace('KYC rejected: ', '')}` :
                                 'To comply with regulations and enable full features, please verify your identity.'}
                            </p>
                        </div>
                    </div>
                </div>

                {!isVerified && !isPendingReview && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {/* WIZARD SIDEBAR */}
                        <div className="md:col-span-1 space-y-6">
                            <div className="space-y-4">
                                <h3 className="text-lg font-bold text-slate-900">Verification Steps</h3>
                                <div className="space-y-3 relative">
                                    <div className="absolute left-[15px] top-4 bottom-4 w-0.5 bg-slate-200" />
                                    {STEPS.map((step, idx) => {
                                        const isCompleted = documents.some(d => d.document_type === step.id);
                                        const isActive = idx === activeStep;
                                        return (
                                            <div 
                                                key={step.id} 
                                                className={`relative flex items-center gap-4 p-3 rounded-xl cursor-pointer transition-colors ${isActive ? 'bg-indigo-50 border border-indigo-100 shadow-sm' : 'hover:bg-slate-50'}`}
                                                onClick={() => setActiveStep(idx)}
                                            >
                                                <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 relative z-10 ${
                                                    isCompleted ? 'bg-emerald-500 text-white shadow-md' :
                                                    isActive ? 'bg-indigo-600 text-white shadow-md ring-4 ring-indigo-100' :
                                                    'bg-slate-200 text-slate-500 border-2 border-white'
                                                }`}>
                                                    {isCompleted ? <CheckCircle2 className="w-4 h-4" /> : <span className="text-sm font-bold">{idx + 1}</span>}
                                                </div>
                                                <div>
                                                    <p className={`text-sm font-semibold ${isActive ? 'text-indigo-900' : 'text-slate-700'}`}>{step.label}</p>
                                                    {step.required && <span className="text-[10px] uppercase font-bold text-rose-500 tracking-wider">Required</span>}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                            
                            <div className="bg-slate-50 p-5 rounded-xl border border-slate-100">
                                <h4 className="font-semibold text-slate-900 text-sm flex items-center gap-2 mb-2">
                                    <ShieldCheck className="w-4 h-4 text-indigo-500"/>
                                    Bank-Grade Security
                                </h4>
                                <p className="text-xs text-slate-600 leading-relaxed">
                                    Your documents are encrypted and stored securely. They are only used for identity verification and are never shared.
                                </p>
                            </div>
                        </div>

                        {/* WIZARD CONTENT */}
                        <div className="md:col-span-2">
                            <Card className="shadow-lg border-slate-200">
                                <AnimatePresence mode="wait">
                                    <motion.div
                                        key={activeStep}
                                        initial={{ opacity: 0, y: 10 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        exit={{ opacity: 0, y: -10 }}
                                        transition={{ duration: 0.2 }}
                                    >
                                        <CardHeader>
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <CardTitle>{STEPS[activeStep].label}</CardTitle>
                                                    <CardDescription className="mt-1">{STEPS[activeStep].desc}</CardDescription>
                                                </div>
                                                <ImageIcon className="w-8 h-8 text-slate-300" />
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            {renderDropzone(STEPS[activeStep])}
                                        </CardContent>
                                        <CardFooter className="bg-slate-50 border-t flex justify-between p-6">
                                            <Button 
                                                variant="outline" 
                                                onClick={() => setActiveStep(prev => Math.max(0, prev - 1))}
                                                disabled={activeStep === 0}
                                            >
                                                <ArrowLeft className="w-4 h-4 mr-2" /> Back
                                            </Button>
                                            
                                            {activeStep < STEPS.length - 1 ? (
                                                <Button onClick={() => setActiveStep(prev => prev + 1)}>
                                                    Next <ArrowRight className="w-4 h-4 ml-2" />
                                                </Button>
                                            ) : (
                                                <Button 
                                                    onClick={handleSubmitForReview} 
                                                    disabled={!canSubmit}
                                                    className={canSubmit ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''}
                                                >
                                                    <CheckCircle2 className="w-4 h-4 mr-2" /> Submit Application
                                                </Button>
                                            )}
                                        </CardFooter>
                                    </motion.div>
                                </AnimatePresence>
                            </Card>
                        </div>
                    </div>
                )}
                
                {isPendingReview && (
                    <div className="text-center p-12 bg-white rounded-2xl border border-slate-200 shadow-sm">
                        <div className="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <CheckCircle2 className="w-10 h-10 text-indigo-500" />
                        </div>
                        <h2 className="text-2xl font-bold text-slate-900 mb-2">Application Received</h2>
                        <p className="text-slate-600 max-w-md mx-auto">
                            Thank you for submitting your documents. Our compliance team is currently reviewing your application. You will be notified via email once the review is complete.
                        </p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
