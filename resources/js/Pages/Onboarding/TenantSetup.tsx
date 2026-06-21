import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, ChevronRight, Building, ArrowRight, Loader2, Link as LinkIcon, Upload, Trash2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { __ } from '@/lib/i18n';

interface UserData {
    id: number;
    name: string;
    email: string;
}

interface Props {
    user: UserData;
    errors: Record<string, string>;
}

export default function TenantSetup({ user, errors }: Props) {
    const [saving, setSaving] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [formData, setFormData] = useState<{
        name: string;
        subdomain: string;
        logo: File | null;
    }>({
        name: '',
        subdomain: '',
        logo: null
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setFormData(prev => ({ ...prev, logo: file }));
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const handleRemoveLogo = () => {
        setFormData(prev => ({ ...prev, logo: null }));
        setPreviewUrl(null);
    };

    const generateSubdomain = (name: string) => {
        return name.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    };

    const handleNameChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newName = e.target.value;
        setFormData(prev => ({
            ...prev,
            name: newName,
            subdomain: prev.subdomain === generateSubdomain(prev.name) ? generateSubdomain(newName) : prev.subdomain
        }));
    };

    const handleComplete = () => {
        setSaving(true);
        router.post(route('onboarding.tenant.store'), formData as any, {
            forceFormData: true,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <div className="min-h-screen bg-muted/20 text-foreground flex flex-col font-sans">
            <header className="border-b bg-background/50 backdrop-blur-md px-6 py-4 flex items-center justify-between sticky top-0 z-50">
                <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 rounded-lg bg-primary text-primary-foreground flex items-center justify-center shadow-sm">
                        <ApplicationLogo className="w-4 h-4 fill-current" />
                    </div>
                    <span className="font-semibold text-sm tracking-tight">{__('general.workspace_onboarding')}</span>
                </div>

                <div className="flex items-center space-x-4 text-xs text-muted-foreground">
                    <span className="hidden sm:inline-block">{__('general.logged_in_as')}<strong className="text-foreground">{user.email}</strong></span>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-muted-foreground hover:text-foreground transition-colors"
                    >{__('general.save_exit')}</Link>
                </div>
            </header>

            <main className="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 lg:p-12 relative overflow-hidden">
                <div className="absolute top-1/4 -start-20 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
                <div className="absolute bottom-1/4 -end-20 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl pointer-events-none" />

                <div className="w-full max-w-xl mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 z-10">
                    <div className="flex items-center space-x-2 text-xs font-medium">
                        {[1, 2].map((s) => (
                            <div key={s} className="flex items-center space-x-2">
                                <div className={`w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300 font-semibold ${s === 1 ? 'bg-primary text-primary-foreground shadow-sm ring-4 ring-primary/10' :
                                    'bg-background text-muted-foreground border border-border'
                                    }`}>
                                    {s}
                                </div>
                                <span className={`hidden sm:inline-block text-xs font-medium ${s === 1 ? 'text-foreground font-semibold' : 'text-muted-foreground'}`}>
                                    {s === 1 && __('general.tenant_setup', 'Workspace Setup')}
                                    {s === 2 && __('general.role_assignment', 'Role Assignment')}
                                </span>
                                {s < 2 && <ChevronRight className="w-3.5 h-3.5 text-muted-foreground mx-1" />}
                            </div>
                        ))}
                    </div>
                </div>

                <Card className="w-full max-w-xl shadow-lg z-10 overflow-visible">
                    <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}>
                        <CardHeader className="pb-6 border-b px-8 pt-8">
                            <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center mb-4 text-foreground">
                                <Building className="w-5 h-5" />
                            </div>
                            <CardTitle className="text-xl sm:text-2xl">{__('general.create_your_workspace', 'Create your workspace')}</CardTitle>
                            <CardDescription className="mt-1.5 leading-relaxed">{__('general.setup_workspace_details', 'Configure the core identity for your team environment.')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6 px-8 py-6">
                            <div className="space-y-2">
                                <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.workspace_name', 'Workspace Name')}<span className="text-destructive">*</span></label>
                                <Input
                                    placeholder="Acme Corp"
                                    value={formData.name}
                                    onChange={handleNameChange}
                                    className="h-10 text-sm font-medium"
                                />
                                {errors.name && <span className="text-xs text-destructive mt-1 block">{errors.name}</span>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center space-x-1.5">
                                    <LinkIcon className="w-3.5 h-3.5 text-blue-500" />
                                    <span>{__('general.subdomain', 'Subdomain')}</span>
                                    <span className="text-destructive">*</span>
                                </label>
                                <div className="flex items-center space-x-2">
                                    <Input
                                        placeholder="acme-corp"
                                        value={formData.subdomain}
                                        onChange={(e) => setFormData(prev => ({ ...prev, subdomain: e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '') }))}
                                        className="h-10 text-sm font-medium"
                                    />
                                    <span className="text-muted-foreground text-sm font-medium">.musoftwares.com</span>
                                </div>
                                {errors.subdomain && <span className="text-xs text-destructive mt-1 block">{errors.subdomain}</span>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.logo', 'Logo')} <span className="text-muted-foreground/70 font-normal lowercase">(optional)</span></label>
                                
                                <div className="flex items-center gap-4">
                                    {previewUrl ? (
                                        <div className="relative group w-20 h-20 rounded-xl overflow-hidden border bg-muted">
                                            <img src={previewUrl} alt="Logo preview" className="w-full h-full object-cover" />
                                            <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <button type="button" onClick={handleRemoveLogo} className="text-white p-1 rounded hover:bg-white/20">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="w-20 h-20 rounded-xl border-2 border-dashed flex items-center justify-center bg-muted/30">
                                            <Building className="w-6 h-6 text-muted-foreground/50" />
                                        </div>
                                    )}
                                    
                                    <div className="flex-1">
                                        <div className="relative">
                                            <Input
                                                type="file"
                                                accept="image/*"
                                                onChange={handleFileChange}
                                                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            />
                                            <Button type="button" variant="outline" className="w-full" disabled={saving}>
                                                <Upload className="w-4 h-4 me-2" /> {__('general.upload_logo', 'Upload Logo')}
                                            </Button>
                                        </div>
                                        <p className="text-xs text-muted-foreground mt-2">Recommended: 256x256px transparent PNG.</p>
                                    </div>
                                </div>
                                {errors.logo && <span className="text-xs text-destructive mt-1 block">{errors.logo}</span>}
                            </div>
                        </CardContent>
                        <CardFooter className="border-t px-8 py-4 flex justify-end bg-muted/30">
                            <Button onClick={handleComplete} size="lg" className="h-11 px-6 rounded-xl font-medium shadow-sm" disabled={saving || !formData.name || !formData.subdomain}>
                                {saving ? <><Loader2 className="w-4 h-4 me-2 animate-spin" />{__('general.saving', 'Saving...')}</> : <>{__('general.continue', 'Continue')}<ArrowRight className="w-4 h-4 ms-2" /></>}
                            </Button>
                        </CardFooter>
                    </motion.div>
                </Card>
            </main>
        </div>
    );
}
