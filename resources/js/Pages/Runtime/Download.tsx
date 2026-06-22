import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Download, Copy, Check, ShieldAlert, MonitorPlay } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Props {
    downloadUrl: string;
    password: string;
    version: string;
}

export default function DownloadPage({ downloadUrl, password, version }: Props) {
    const [copied, setCopied] = useState(false);

    const copyPassword = () => {
        navigator.clipboard.writeText(password);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <AuthenticatedLayout>
            <Head title={__('general.musoftware_runtime_download')} />
            <div className="max-w-3xl mx-auto py-8">
                <Card className="border-emerald-100 shadow-sm relative overflow-hidden">
                    <div className="absolute top-0 start-0 w-1 h-full bg-emerald-500"></div>
                    <CardHeader className="pb-4">
                        <div className="flex items-center gap-4">
                            <div className="bg-emerald-100 p-3 rounded-full text-emerald-600">
                                <MonitorPlay size={28} />
                            </div>
                            <div>
                                <CardTitle className="text-2xl font-bold">{__('general.musoftware_runtime')}</CardTitle>
                                <CardDescription className="text-base mt-1">
                                    Version {version} for Windows
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-8">
                            <div className="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-lg flex items-start gap-3">
                                <ShieldAlert className="shrink-0 mt-0.5" size={20} />
                                <div className="text-sm">
                                    <strong>Important Security Notice:</strong> {__('general.because_this_software_integrates_deeply')}</div>
                            </div>

                            <div className="space-y-4 bg-slate-50 p-6 rounded-lg border border-slate-100">
                                <div>
                                    <h3 className="font-semibold text-lg mb-2">Step 1: Copy the Password</h3>
                                    <p className="text-sm text-slate-600 mb-3">
                                        {__('general.you_will_need_this_password_to_extract_t')}</p>
                                    <div className="flex items-center gap-2 max-w-sm">
                                        <Input readOnly value={password} className="font-mono text-center font-bold tracking-wider" />
                                        <Button variant="secondary" onClick={copyPassword} className="shrink-0">
                                            {copied ? <Check size={16} className="text-emerald-500" /> : <Copy size={16} />}
                                            <span className="ms-2">{copied ? 'Copied!' : 'Copy'}</span>
                                        </Button>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-slate-200">
                                    <h3 className="font-semibold text-lg mb-2">Step 2: Download the File</h3>
                                    <p className="text-sm text-slate-600 mb-4">
                                        {__('general.download_the_zip_file_extract_it_using_t')}</p>
                                    <a href={downloadUrl} download>
                                        <Button size="lg" className="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700">
                                            <Download className="me-2" size={18} />
                                            Download Runtime ({version})
                                        </Button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
