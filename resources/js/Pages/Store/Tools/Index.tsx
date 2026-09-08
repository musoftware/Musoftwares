import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { PageHeroHeader } from '@/Components/ui/PageHeroHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle 
} from '@/Components/ui/dialog';
import { 
    Wrench, Laptop, CheckCircle2, Key, ArrowRight, 
    Search, Download, Monitor, ShieldCheck, Mail, Sparkles 
} from 'lucide-react';
import { __ } from '@/lib/i18n';

interface SoftwareItem {
    id: number;
    name: string;
    default_status: string;
    requires_payment: boolean;
    price: number;
    currency: string;
    payment_instructions: string | null;
    whatsapp_number: string | null;
    has_license: boolean;
    license_expires_at: string | null;
}

interface StoreProps {
    softwares: SoftwareItem[];
    userLicensesCount: number;
}

export default function StoreToolsIndex({ softwares = [], userLicensesCount = 0 }: StoreProps) {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    const [searchQuery, setSearchQuery] = useState('');
    const [selectedSoftware, setSelectedSoftware] = useState<SoftwareItem | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: user?.email || '',
        name: user?.name || '',
        phone: user?.mobile_1 || '',
        device_id: '',
    });

    const filteredSoftwares = softwares.filter(sw => 
        sw.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const handleOpenPurchase = (software: SoftwareItem) => {
        setSelectedSoftware(software);
        setData(prev => ({
            ...prev,
            email: user?.email || '',
            name: user?.name || '',
            phone: user?.mobile_1 || '',
            device_id: '',
        }));
        setIsDialogOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedSoftware) return;

        post(route('store.tools.purchase', selectedSoftware.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsDialogOpen(false);
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Software & Tools Store | متجر البرامج والأدوات" />

            <PageShell>
                <div className="space-y-8">
                    {/* Top Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/5 dark:border-white/10 pb-6">
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/40">
                                    <Sparkles className="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                    Desktop Automation & Tools
                                </span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-[#1d1d1f] dark:text-[#f8fafc]">
                                Software & Tools Store
                            </h1>
                            <p className="text-sm text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 mt-1 max-w-2xl font-sans">
                                Professional Windows desktop software and automation tools. Purchase or activate licenses instantly linked to your email address for seamless device unlock.
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            <Link
                                href={route('store.tools.my-licenses')}
                                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-black/10 dark:border-white/10 text-xs sm:text-sm font-semibold text-[#1d1d1f] dark:text-zinc-100 hover:bg-[#f5f5f7] dark:hover:bg-zinc-800 transition-all shadow-xs"
                            >
                                <Key className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                My Licenses ({userLicensesCount})
                            </Link>
                        </div>
                    </div>

                    {/* Search Bar */}
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="relative w-full sm:w-96">
                            <Search className="w-4 h-4 text-[#1d1d1f]/40 dark:text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Search software by name (e.g. WAContactsExtract)..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 text-sm bg-white dark:bg-zinc-900/80 border border-black/10 dark:border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0071e3] text-[#1d1d1f] dark:text-[#f8fafc] placeholder:text-[#1d1d1f]/40 dark:placeholder:text-zinc-500"
                            />
                        </div>

                        <div className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400 font-sans">
                            Showing <span className="font-semibold text-[#1d1d1f] dark:text-white">{filteredSoftwares.length}</span> tools
                        </div>
                    </div>

                    {/* Software Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredSoftwares.map((software) => (
                            <div
                                key={software.id}
                                className="group relative flex flex-col justify-between bg-white dark:bg-zinc-900/90 border border-black/5 dark:border-white/10 rounded-[24px] p-6 hover:border-[#0071e3]/40 dark:hover:border-[#2997ff]/40 hover:shadow-md transition-all shadow-xs"
                            >
                                <div>
                                    <div className="flex items-start justify-between gap-3 mb-4">
                                        <div className="w-12 h-12 rounded-2xl bg-blue-500/10 dark:bg-blue-500/20 flex items-center justify-center text-[#0071e3] dark:text-[#2997ff] shrink-0">
                                            <Laptop className="w-6 h-6" />
                                        </div>

                                        {software.has_license ? (
                                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                                Active License
                                            </span>
                                        ) : (
                                            <span className="text-xs font-semibold px-3 py-1 rounded-full bg-[#f5f5f7] dark:bg-zinc-800 text-[#1d1d1f]/70 dark:text-zinc-300 border border-black/5 dark:border-white/10">
                                                {software.requires_payment && software.price > 0 
                                                    ? `${software.price} ${software.currency}` 
                                                    : 'Free License'}
                                            </span>
                                        )}
                                    </div>

                                    <h3 className="text-lg font-bold text-[#1d1d1f] dark:text-[#f8fafc] group-hover:text-[#0071e3] dark:group-hover:text-[#2997ff] tracking-tight transition-colors">
                                        {software.name}
                                    </h3>

                                    <p className="text-xs text-[#1d1d1f]/60 dark:text-[#f8fafc]/60 font-sans mt-2 leading-relaxed">
                                        Windows automation tool. Seamless automated check-in and email-based device activation.
                                    </p>

                                    {software.license_expires_at && (
                                        <div className="mt-3 text-[11px] text-emerald-600 dark:text-emerald-400 font-mono">
                                            Valid until: {new Date(software.license_expires_at).toLocaleDateString()}
                                        </div>
                                    )}
                                </div>

                                <div className="mt-6 pt-4 border-t border-black/5 dark:border-white/10 flex items-center justify-between">
                                    <div className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400 font-mono">
                                        Windows x64 / x86
                                    </div>

                                    <Button
                                        onClick={() => handleOpenPurchase(software)}
                                        variant={software.has_license ? 'outline' : 'default'}
                                        size="sm"
                                        className="rounded-xl px-4 text-xs font-semibold"
                                    >
                                        {software.has_license ? 'Renew / Bind Device' : 'Activate Software'}
                                        <ArrowRight className="w-3.5 h-3.5 ms-1.5" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>

                    {filteredSoftwares.length === 0 && (
                        <div className="text-center py-16 bg-white dark:bg-zinc-900/60 rounded-3xl border border-black/5 dark:border-white/10">
                            <Laptop className="w-12 h-12 text-[#1d1d1f]/20 dark:text-zinc-600 mx-auto mb-3" />
                            <h3 className="text-base font-semibold text-[#1d1d1f] dark:text-white">No software tools found</h3>
                            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400 mt-1">Try refining your search term.</p>
                        </div>
                    )}
                </div>

                {/* Purchase / Activation Modal */}
                <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                    <DialogContent className="sm:max-w-md rounded-[24px]">
                        <DialogHeader>
                            <div className="w-10 h-10 rounded-xl bg-[#0071e3]/10 dark:bg-[#0071e3]/20 flex items-center justify-center text-[#0071e3] dark:text-[#2997ff] mb-2">
                                <Key className="w-5 h-5" />
                            </div>
                            <DialogTitle className="text-lg font-bold">
                                Activate Software: {selectedSoftware?.name}
                            </DialogTitle>
                            <DialogDescription className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400 font-sans">
                                Enter the email address to bind this license to. When you launch the program on your computer and type this email, your device will activate automatically.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-4 py-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="email" className="text-xs font-semibold">
                                    Email Address (Required)
                                </Label>
                                <div className="relative">
                                    <Mail className="w-4 h-4 text-[#1d1d1f]/40 dark:text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                    <Input
                                        id="email"
                                        type="email"
                                        required
                                        placeholder="your-email@example.com"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="pl-9 text-sm rounded-xl"
                                    />
                                </div>
                                {errors.email && <p className="text-xs text-rose-500">{errors.email}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="device_id" className="text-xs font-semibold">
                                    Device / Hardware ID (Optional)
                                </Label>
                                <div className="relative">
                                    <Monitor className="w-4 h-4 text-[#1d1d1f]/40 dark:text-zinc-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                    <Input
                                        id="device_id"
                                        type="text"
                                        placeholder="e.g. DESKTOP-XYZ123 (or enter inside desktop app)"
                                        value={data.device_id}
                                        onChange={(e) => setData('device_id', e.target.value)}
                                        className="pl-9 text-sm rounded-xl font-mono"
                                    />
                                </div>
                                <p className="text-[11px] text-[#1d1d1f]/40 dark:text-zinc-500">
                                    If left empty, you can simply launch the desktop tool and enter your email there to activate your device on demand.
                                </p>
                            </div>

                            <div className="p-3 bg-[#f5f5f7] dark:bg-zinc-800/60 rounded-xl border border-black/5 dark:border-white/10 space-y-1">
                                <div className="flex items-center justify-between text-xs font-semibold">
                                    <span>License Validity:</span>
                                    <span className="text-emerald-600 dark:text-emerald-400">1 Year (365 Days)</span>
                                </div>
                                <div className="flex items-center justify-between text-xs font-semibold">
                                    <span>Pricing:</span>
                                    <span>
                                        {selectedSoftware?.requires_payment && selectedSoftware?.price > 0
                                            ? `${selectedSoftware?.price} ${selectedSoftware?.currency}`
                                            : 'Free License'}
                                    </span>
                                </div>
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsDialogOpen(false)}
                                    className="rounded-xl"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-xl bg-[#0071e3] hover:bg-[#0077ed] text-white"
                                >
                                    {processing ? 'Activating...' : 'Confirm & Activate'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </PageShell>
        </AuthenticatedLayout>
    );
}
