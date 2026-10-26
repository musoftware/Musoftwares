import React, { useCallback, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Download, ShieldCheck, Trash2, UploadCloud, XCircle } from 'lucide-react';
import { useDropzone } from 'react-dropzone';
import ClientPage, { Card, PrimaryAction } from '@/Components/Client/ClientPage';
import { useToast } from '@/Components/ui/use-toast';
import { useConfirm } from '@/hooks/useConfirm';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

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
    kycStatus: KycStatus;
    documents: DocumentType[];
    missingDocs: string[];
    requiredDocs: string[];
}

const DOCS = [
    { id: 'id_front', label: 'client.kyc_step_id_label', desc: 'client.kyc_step_id_desc', required: true },
    { id: 'selfie', label: 'client.kyc_step_selfie_label', desc: 'client.kyc_step_selfie_desc', required: true },
    { id: 'proof_of_address', label: 'client.kyc_step_address_label', desc: 'client.kyc_step_address_desc', required: false },
];

/** Identity verification as one short list: where you stand, each document, then one submit button. */
export default function KycIndex({ kycStatus, documents, missingDocs }: Props) {
    const { toast } = useToast();
    const { confirm, confirmDialog } = useConfirm();
    const [uploadingType, setUploadingType] = useState<string | null>(null);
    const { data, setData, post, processing, errors, reset } = useForm({ document_type: '', document: null as File | null });

    const isVerified = kycStatus.isVerified;
    const isPendingReview = !isVerified && !!kycStatus.notes?.includes('submitted');
    const isRejected = !isVerified && !!kycStatus.notes?.includes('rejected');
    const canSubmit = missingDocs.length === 0 && !isVerified && !isPendingReview;
    const state = isVerified ? 'verified' : isPendingReview ? 'review' : isRejected ? 'rejected' : 'required';

    const onDrop = useCallback((files: File[], docType: string) => {
        const file = files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            toast({ title: __('client.kyc_file_too_large'), description: __('client.kyc_max_size'), variant: 'destructive' });
            return;
        }
        setData({ document_type: docType, document: file });
        setUploadingType(docType);
    }, [setData, toast]);

    const upload = () => post(route('kyc.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            toast({ title: __('client.kyc_uploaded'), description: __('client.kyc_uploaded_desc') });
            reset();
            setUploadingType(null);
        },
        onError: () => setUploadingType(null),
    });

    const submitForReview = () => router.post(route('kyc.submit'), {}, {
        preserveScroll: true,
        onSuccess: () => toast({ title: __('client.kyc_submitted'), description: __('client.kyc_submitted_desc') }),
    });

    const remove = async (id: number) => {
        if (!(await confirm({ title: __('client.kyc_delete_title'), description: __('client.kyc_delete_confirm'), variant: 'danger' }))) return;
        router.delete(route('kyc.delete', id), {
            preserveScroll: true,
            onSuccess: () => toast({ title: __('client.kyc_deleted'), description: __('client.kyc_deleted_desc') }),
        });
    };

    const StatusIcon = { verified: CheckCircle2, review: AlertCircle, rejected: XCircle, required: ShieldCheck }[state];

    return (
        <ClientPage title={__('client_account.kyc.title')} hint={__('client_account.kyc.hint')} width="sm">
            <Card className={cn(
                state === 'verified' && 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30',
                state === 'review' && 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30',
                state === 'rejected' && 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/30',
            )}>
                <div className="flex items-start gap-3">
                    <StatusIcon className={cn('mt-0.5 h-6 w-6 shrink-0',
                        state === 'verified' ? 'text-emerald-600' : state === 'review' ? 'text-amber-600' : state === 'rejected' ? 'text-rose-600' : 'text-[#0071e3]')} />
                    <div>
                        <h2 className="font-bold">
                            {state === 'verified' ? __('client.kyc_identity_verified') : state === 'review' ? __('client.kyc_under_review')
                                : state === 'rejected' ? __('client.kyc_rejected') : __('client.kyc_verification_required')}
                        </h2>
                        <p className="mt-1 text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                            {state === 'verified' ? __('client.kyc_verified_desc') : state === 'review' ? __('client.kyc_review_desc')
                                : state === 'rejected' ? __('client.kyc_rejected_desc', { reason: kycStatus.notes?.replace('KYC rejected: ', '') ?? '' })
                                : __('client.kyc_required_desc')}
                        </p>
                    </div>
                </div>
            </Card>

            {!isVerified && !isPendingReview && (
                <>
                    <ol className="space-y-3">
                        {DOCS.map((doc, i) => (
                            <li key={doc.id}>
                                <Card>
                                    <div className="mb-3 flex items-start gap-3">
                                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#0071e3] text-sm font-bold text-white">{i + 1}</span>
                                        <div>
                                            <h3 className="font-semibold">
                                                {__(doc.label)}
                                                <span className="ms-2 text-xs font-normal text-[#1d1d1f]/50 dark:text-zinc-500">
                                                    {doc.required ? __('client_account.kyc.required') : __('client_account.kyc.optional')}
                                                </span>
                                            </h3>
                                            <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__(doc.desc)}</p>
                                        </div>
                                    </div>
                                    <DocumentDrop
                                        docId={doc.id}
                                        uploaded={documents.find((d) => d.document_type === doc.id)}
                                        pendingFile={data.document && data.document_type === doc.id ? data.document : null}
                                        canRemove={!isPendingReview}
                                        processing={processing}
                                        error={uploadingType === doc.id ? errors.document : undefined}
                                        onDrop={onDrop}
                                        onUpload={upload}
                                        onRemove={remove}
                                    />
                                </Card>
                            </li>
                        ))}
                    </ol>

                    <div className="flex flex-col items-start gap-2">
                        <PrimaryAction onClick={canSubmit ? submitForReview : undefined} icon={<CheckCircle2 className="h-4 w-4" />}
                            className={cn(!canSubmit && 'pointer-events-none opacity-50')}>
                            {__('client_account.kyc.submit')}
                        </PrimaryAction>
                        {!canSubmit && <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_account.kyc.submit_hint')}</p>}
                        <p className="flex items-center gap-1.5 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                            <ShieldCheck className="h-3.5 w-3.5" />{__('general.your_documents_are_encrypted_and_stored')}
                        </p>
                    </div>
                </>
            )}
            {confirmDialog}
        </ClientPage>
    );
}

function DocumentDrop({ docId, uploaded, pendingFile, canRemove, processing, error, onDrop, onUpload, onRemove }: {
    docId: string;
    uploaded?: DocumentType;
    pendingFile: File | null;
    canRemove: boolean;
    processing: boolean;
    error?: string;
    onDrop: (files: File[], docType: string) => void;
    onUpload: () => void;
    onRemove: (id: number) => void;
}) {
    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop: (files) => onDrop(files, docId),
        accept: { 'image/jpeg': [], 'image/png': [], 'application/pdf': [] },
        maxSize: 5242880,
        multiple: false,
    });

    if (uploaded) {
        return (
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-emerald-50 p-3 text-sm dark:bg-emerald-950/30">
                <span className="flex min-w-0 items-center gap-2 text-emerald-800 dark:text-emerald-300">
                    <CheckCircle2 className="h-4 w-4 shrink-0" /><span className="truncate" dir="ltr">{uploaded.original_filename}</span>
                </span>
                <span className="flex items-center gap-4">
                    <a href={route('kyc.download', uploaded.id)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 font-semibold text-[#0071e3]">
                        <Download className="h-4 w-4" />{__('general.view')}
                    </a>
                    {canRemove && uploaded.status !== 'approved' && (
                        <button type="button" onClick={() => onRemove(uploaded.id)} className="inline-flex items-center gap-1 font-semibold text-rose-600">
                            <Trash2 className="h-4 w-4" />{__('general.remove')}
                        </button>
                    )}
                </span>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div {...getRootProps()} className={cn('flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-6 text-center transition-colors',
                isDragActive ? 'border-[#0071e3] bg-[#0071e3]/5' : 'border-black/10 bg-[#f5f5f7] hover:border-black/20 dark:border-white/10 dark:bg-zinc-800')}>
                <input {...getInputProps()} />
                <UploadCloud className="mb-2 h-7 w-7 text-[#0071e3]" />
                {pendingFile ? (
                    <p className="text-sm font-semibold" dir="ltr">{pendingFile.name}</p>
                ) : (
                    <>
                        <p className="text-sm font-semibold">{__('client_account.kyc.choose_file')}</p>
                        <p className="mt-1 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('general.jpg_png_pdf_up_to_5mb')}</p>
                    </>
                )}
            </div>
            {pendingFile && (
                <PrimaryAction onClick={processing ? undefined : onUpload} icon={<UploadCloud className="h-4 w-4" />} className={cn(processing && 'opacity-60')}>
                    {processing ? __('client.kyc_uploading') : __('client.kyc_confirm_upload')}
                </PrimaryAction>
            )}
            {error && <p className="text-xs text-rose-600">{error}</p>}
        </div>
    );
}
