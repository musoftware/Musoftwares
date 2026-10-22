import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { t } from './t';

export interface Release {
    id: number;
    version: string;
    size: number;
    is_published: boolean;
    released_at: string | null;
}

const formatMb = (bytes: number) => `${(bytes / 1024 / 1024).toFixed(1)} MB`;

/** Upload the new setup file. Users get the update by themselves. */
export function ReleasePanel({ productId, releases, feedUrl }: { productId: number; releases: Release[]; feedUrl: string }) {
    const { errors } = usePage().props as { errors?: Record<string, string> };
    const [files, setFiles] = useState<File[]>([]);
    const [version, setVersion] = useState('');
    const [notes, setNotes] = useState('');
    const [uploading, setUploading] = useState(false);

    const upload = () => {
        router.post(
            `/admin/software-products/${productId}/releases`,
            { files, version, release_notes: notes },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setUploading(true),
                onFinish: () => setUploading(false),
                onSuccess: () => {
                    setFiles([]);
                    setVersion('');
                    setNotes('');
                },
            },
        );
    };

    return (
        <div className="space-y-6">
            <div className="rounded-md border p-4 space-y-4">
                <h3 className="font-semibold">{t('Publish a new version', 'نشر نسخة جديدة')}</h3>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Choose the new setup file (.exe) and press Publish. Every installed copy updates by itself.',
                        'اختار ملف التثبيت الجديد (.exe) واضغط نشر. كل النسخ المثبتة بتتحدث لوحدها.',
                    )}
                </p>
                <label className="block space-y-1 text-sm">
                    <span>{t('Setup file (you can add its .blockmap file too)', 'ملف التثبيت (ممكن تضيف ملف .blockmap معاه)')}</span>
                    <Input type="file" accept=".exe,.blockmap" multiple onChange={(e) => setFiles(Array.from(e.target.files ?? []))} />
                    {errors?.files && <span className="text-red-600">{errors.files}</span>}
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <label className="space-y-1">
                        <span>{t('Version (empty = read from file name)', 'رقم النسخة (فاضي = من اسم الملف)')}</span>
                        <Input value={version} onChange={(e) => setVersion(e.target.value)} placeholder="2.1.0" />
                        {errors?.version && <span className="text-red-600">{errors.version}</span>}
                    </label>
                    <label className="space-y-1">
                        <span>{t('What is new (optional)', 'إيه الجديد (اختياري)')}</span>
                        <Input value={notes} onChange={(e) => setNotes(e.target.value)} />
                    </label>
                </div>
                <Button type="button" onClick={upload} disabled={uploading || files.length === 0}>
                    {uploading ? t('Uploading, please wait...', 'جاري الرفع، استنى...') : t('Publish', 'نشر')}
                </Button>
            </div>

            <div className="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('Version', 'النسخة')}</TableHead>
                            <TableHead>{t('Size', 'الحجم')}</TableHead>
                            <TableHead>{t('Date', 'التاريخ')}</TableHead>
                            <TableHead>{t('Status', 'الحالة')}</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {releases.map((release) => (
                            <TableRow key={release.id}>
                                <TableCell>{release.version}</TableCell>
                                <TableCell>{formatMb(release.size)}</TableCell>
                                <TableCell>{release.released_at ? release.released_at.slice(0, 10) : ''}</TableCell>
                                <TableCell>{release.is_published ? t('Published', 'منشورة') : t('Hidden', 'مخفية')}</TableCell>
                                <TableCell className="space-x-2 text-end">
                                    <Button variant="outline" size="sm" onClick={() => router.post(`/admin/software-releases/${release.id}/toggle`, {}, { preserveScroll: true })}>
                                        {release.is_published ? t('Hide', 'إخفاء') : t('Publish', 'نشر')}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            confirm(t(`Delete version ${release.version}?`, `حذف النسخة ${release.version}؟`)) &&
                                            router.delete(`/admin/software-releases/${release.id}`, { preserveScroll: true })
                                        }
                                    >
                                        {t('Delete', 'حذف')}
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            <p className="text-xs text-muted-foreground break-all" dir="ltr">
                {t('Update address used by the program', 'عنوان التحديث اللي البرنامج بيقراه')}: {feedUrl}
            </p>
        </div>
    );
}
