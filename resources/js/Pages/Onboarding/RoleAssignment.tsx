import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Check, ChevronRight, Users, ArrowRight, Loader2, Plus, Mail, Shield, CheckCircle2, AlertTriangle, ArrowLeft } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import confetti from 'canvas-confetti';
import { __ } from '@/lib/i18n';

interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Role {
    id: string;
    name: string;
}

interface Props {
    tenant: any;
    teamMembers: TeamMember[];
    roles: Role[];
    errors: Record<string, string>;
}

export default function RoleAssignment({ tenant, teamMembers, roles, errors }: Props) {
    const [saving, setSaving] = useState(false);
    const [inviting, setInviting] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        role: ''
    });

    const handleInvite = () => {
        setInviting(true);
        router.post(route('onboarding.tenant.invite'), formData, {
            preserveScroll: true,
            onSuccess: () => setFormData({ name: '', email: '', role: '' }),
            onFinish: () => setInviting(false),
        });
    };

    const handleFinish = () => {
        setSaving(true);
        confetti({
            particleCount: 120,
            spread: 80,
            origin: { y: 0.6 }
        });
        
        router.post(route('onboarding.tenant.finish'), {}, {
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

                <div className="w-full max-w-2xl mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 z-10">
                    <div className="flex items-center space-x-2 text-xs font-medium">
                        {[1, 2].map((s) => (
                            <div key={s} className="flex items-center space-x-2">
                                <div className={`w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300 font-semibold ${s === 2 ? 'bg-primary text-primary-foreground shadow-sm ring-4 ring-primary/10' :
                                    s < 2 ? 'bg-muted text-muted-foreground' :
                                    'bg-background text-muted-foreground border border-border'
                                    }`}>
                                    {s < 2 ? <Check className="w-3.5 h-3.5" /> : s}
                                </div>
                                <span className={`hidden sm:inline-block text-xs font-medium ${s === 2 ? 'text-foreground font-semibold' : 'text-muted-foreground'}`}>
                                    {s === 1 && (__('general.tenant_setup') ?? 'Workspace Setup')}
                                    {s === 2 && (__('general.role_assignment') ?? 'Role Assignment')}
                                </span>
                                {s < 2 && <ChevronRight className="w-3.5 h-3.5 text-muted-foreground mx-1" />}
                            </div>
                        ))}
                    </div>
                </div>

                <Card className="w-full max-w-2xl shadow-lg z-10 overflow-visible">
                    <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}>
                        <CardHeader className="pb-6 border-b px-8 pt-8">
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center mb-4 text-foreground">
                                        <Users className="w-5 h-5" />
                                    </div>
                                    <CardTitle className="text-xl sm:text-2xl">{(__('general.invite_team_members') ?? 'Invite Team Members')}</CardTitle>
                                    <CardDescription className="mt-1.5 leading-relaxed">{(__('general.invite_team_desc') ?? 'Assign roles to your team members for the workspace:')} <strong className="text-foreground">{tenant.name}</strong></CardDescription>
                                </div>
                                {tenant.logo && (
                                    <div className="w-16 h-16 rounded-xl border overflow-hidden shadow-sm hidden sm:block">
                                        <img src={`/storage/${tenant.logo}`} alt="Workspace Logo" className="w-full h-full object-cover" />
                                    </div>
                                )}
                            </div>
                        </CardHeader>
                        
                        <CardContent className="space-y-6 px-8 py-6">
                            {/* Invite Form */}
                            <div className="bg-muted/30 p-5 rounded-xl border shadow-sm">
                                <h3 className="text-sm font-semibold mb-4 flex items-center"><Plus className="w-4 h-4 me-2"/> {(__('general.add_new_member') ?? 'Add New Member')}</h3>
                                
                                <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div className="md:col-span-4 space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{(__('general.name') ?? 'Name')}</label>
                                        <Input
                                            placeholder={__('general.jane_doe')}
                                            value={formData.name}
                                            onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                                            className="h-9 text-sm"
                                        />
                                        {errors.name && <span className="text-xs text-destructive">{errors.name}</span>}
                                    </div>
                                    <div className="md:col-span-4 space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center"><Mail className="w-3 h-3 me-1"/>{(__('general.email') ?? 'Email')}</label>
                                        <Input
                                            placeholder="jane@example.com"
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                                            className="h-9 text-sm"
                                        />
                                        {errors.email && <span className="text-xs text-destructive">{errors.email}</span>}
                                    </div>
                                    <div className="md:col-span-4 space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center"><Shield className="w-3 h-3 me-1"/>{(__('general.role') ?? 'Role')}</label>
                                        <PremiumCombobox
                                            value={formData.role}
                                            onChange={(val) => setFormData(prev => ({ ...prev, role: String(val) }))}
                                            options={roles}
                                            placeholder={__('general.select_role')}
                                            searchPlaceholder="Search role"
                                        />
                                        {errors.role && <span className="text-xs text-destructive">{errors.role}</span>}
                                    </div>
                                </div>
                                <div className="mt-4 flex justify-end">
                                    <Button onClick={handleInvite} size="sm" variant="secondary" disabled={inviting || !formData.name || !formData.email || !formData.role}>
                                        {inviting ? <><Loader2 className="w-3.5 h-3.5 me-2 animate-spin" />{(__('general.inviting') ?? 'Inviting...')}</> : <>{(__('general.send_invite') ?? 'Send Invite')}</>}
                                    </Button>
                                </div>
                            </div>

                            {/* Team Members List */}
                            <div className="space-y-3">
                                <h3 className="text-sm font-semibold">{(__('general.team_members') ?? 'Team Members')} ({teamMembers.length})</h3>
                                {teamMembers.length === 0 ? (
                                    <div className="text-center py-8 border border-dashed rounded-xl bg-muted/10 text-muted-foreground">
                                        <Users className="w-8 h-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">{(__('general.no_members_yet') ?? 'No team members added yet. You can invite them above or skip for now.')}</p>
                                    </div>
                                ) : (
                                    <div className="border rounded-xl divide-y overflow-hidden">
                                        {teamMembers.map((member) => (
                                            <div key={member.id} className="flex items-center justify-between p-3 bg-card hover:bg-muted/10 transition-colors">
                                                <div className="flex items-center space-x-3">
                                                    <div className="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs uppercase">
                                                        {member.name.substring(0, 2)}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium">{member.name}</p>
                                                        <p className="text-xs text-muted-foreground">{member.email}</p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-3">
                                                    <span className="px-2 py-1 bg-secondary text-secondary-foreground text-xs rounded-md font-medium border shadow-sm">
                                                        {roles.find(r => r.id === member.role)?.name || member.role || 'No Role'}
                                                    </span>
                                                    <CheckCircle2 className="w-4 h-4 text-emerald-500" />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                        <CardFooter className="border-t px-8 py-4 flex justify-between items-center bg-muted/30">
                            <Link href={route('onboarding.tenant.setup')} className="text-sm text-muted-foreground hover:text-foreground font-medium flex items-center transition-colors">
                                <ArrowLeft className="w-4 h-4 me-1.5" /> {__('general.back')}
                            </Link>
                            <Button onClick={handleFinish} size="lg" className="h-11 px-8 rounded-xl font-medium shadow-md" disabled={saving}>
                                {saving ? <><Loader2 className="w-4 h-4 me-2 animate-spin" />{(__('general.finishing') ?? 'Finishing setup...')}</> : <>{(__('general.finish_setup') ?? 'Finish Setup')} <Check className="w-4 h-4 ms-2" /></>}
                            </Button>
                        </CardFooter>
                    </motion.div>
                </Card>
            </main>
        </div>
    );
}
