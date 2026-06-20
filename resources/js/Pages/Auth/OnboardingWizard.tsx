import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import confetti from 'canvas-confetti';
import axios from 'axios';
import {
    Check, ChevronRight, Globe, Phone, DollarSign, Lock, AlertTriangle,
    ArrowRight, ArrowLeft, Loader2, Sparkles, MapPin, Send, MessageSquare, Search
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { PremiumCombobox } from '@/Components/ui/PremiumCombobox';
import { __ } from '@/lib/i18n';

interface UserData {
    id: number;
    name: string;
    email: string;
    country: string;
    city: string;
    mobile_1: string;
    mobile_2: string;
    telegram_username: string;
    currency_id: number | null;
}

interface Props {
    user: UserData;
    countries: string[];
}

export default function OnboardingWizard({ user, countries }: Props) {
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({
        country: user.country || 'United States',
        city: user.city || '',
        mobile_1: user.mobile_1 || '',
        mobile_2: user.mobile_2 || '',
        telegram_username: user.telegram_username || '',
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved'>('idle');
    const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Dynamic cities
    const [cities, setCities] = useState<string[]>([]);

    useEffect(() => {
        if (!formData.country) return;

        let isMounted = true;

        axios.get(route('onboarding.cities', { countryName: formData.country }))
            .then(res => {
                if (isMounted) {
                    setCities(res.data || []);
                }
            })
            .catch(err => console.error('Failed to fetch cities:', err));

        return () => { isMounted = false; };
    }, [formData.country]);

    // Auto-select first city if empty or country changes
    useEffect(() => {
        if (!formData.city && cities.length > 0) {
            setFormData(prev => ({ ...prev, city: cities[0] }));
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [formData.country, cities]);

    // Autosave functionality
    useEffect(() => {
        if (saveTimeoutRef.current) clearTimeout(saveTimeoutRef.current);
        setSaveStatus('saving');

        saveTimeoutRef.current = setTimeout(() => {
            router.post(route('onboarding.store'), {
                ...formData,
                action: 'autosave',
                step: step,
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setSaveStatus('saved'),
                onError: () => setSaveStatus('idle'),
            });
        }, 800);

        return () => {
            if (saveTimeoutRef.current) clearTimeout(saveTimeoutRef.current);
        };
    }, [formData, step]);

    const validateStep = (currentStep: number) => {
        const newErrors: Record<string, string> = {};
        if (currentStep === 1) {
            if (!formData.country) newErrors.country = 'Country is required.';
            if (!formData.city || formData.city.trim() === '') newErrors.city = 'City is required.';
        } else if (currentStep === 2) {
            if (!formData.mobile_1 || formData.mobile_1.trim() === '') {
                newErrors.mobile_1 = 'Primary mobile number is required.';
            } else if (formData.mobile_1.length < 7) {
                newErrors.mobile_1 = 'Please enter a valid phone number.';
            }
        } else if (currentStep === 3) {
            // Step 3 validation (if needed in the future)
        }
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const nextStep = () => {
        if (validateStep(step)) {
            setStep(prev => Math.min(prev + 1, 2));
        }
    };

    const prevStep = () => {
        setStep(prev => Math.max(prev - 1, 1));
    };

    const handleComplete = () => {
        if (!validateStep(2)) return;

        setSaving(true);
        router.post(route('onboarding.store'), {
            ...formData,
            action: 'complete',
            step: 2,
        }, {
            onSuccess: () => {
                confetti({
                    particleCount: 120,
                    spread: 80,
                    origin: { y: 0.6 }
                });
            },
            onFinish: () => setSaving(false),
        });
    };

    // Default top 8 cities if empty
    const defaultCities = ['New York', 'San Francisco', 'Los Angeles', 'Chicago', 'Austin', 'Miami', 'Seattle', 'Denver'];
    const currentCitiesList = cities.length > 0 ? cities : defaultCities;
    const uniqueCities = Array.from(new Set(currentCitiesList));

    return (
        <div className="min-h-screen bg-muted/20 text-foreground flex flex-col font-sans">
            {/* Top Minimal Header */}
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

            {/* Main Centered Onboarding Wizard Container */}
            <main className="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 lg:p-12 relative overflow-hidden">
                {/* Background Ambient Glow */}
                <div className="absolute top-1/4 -left-20 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none" />
                <div className="absolute bottom-1/4 -right-20 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl pointer-events-none" />

                {/* Progress Indicators */}
                <div className="w-full max-w-xl mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 z-10">
                    <div className="flex items-center space-x-2 text-xs font-medium">
                        {[1, 2].map((s) => (
                            <div key={s} className="flex items-center space-x-2">
                                <div className={`w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300 font-semibold ${s === step ? 'bg-primary text-primary-foreground shadow-sm ring-4 ring-primary/10' :
                                        s < step ? 'bg-muted text-muted-foreground' :
                                            'bg-background text-muted-foreground border border-border'
                                    }`}>
                                    {s < step ? <Check className="w-3.5 h-3.5" /> : s}
                                </div>
                                <span className={`hidden sm:inline-block text-xs font-medium ${s === step ? 'text-foreground font-semibold' : 'text-muted-foreground'}`}>
                                    {s === 1 && 'Location'}
                                    {s === 2 && 'Contact'}
                                </span>
                                {s < 2 && <ChevronRight className="w-3.5 h-3.5 text-muted-foreground mx-1" />}
                            </div>
                        ))}
                    </div>

                    {/* Autosave Status */}
                    <div className="flex items-center space-x-2 text-xs text-muted-foreground bg-background px-3 py-1.5 rounded-full border shadow-sm">
                        <div className={`w-2 h-2 rounded-full ${saveStatus === 'saving' ? 'bg-amber-500 animate-pulse' :
                                saveStatus === 'saved' ? 'bg-emerald-500' : 'bg-muted-foreground'
                            }`} />
                        <span>{saveStatus === 'saving' ? 'Saving progress...' : saveStatus === 'saved' ? 'Saved' : 'Ready'}</span>
                    </div>
                </div>

                {/* Compact Wizard Card */}
                <Card className="w-full max-w-xl shadow-lg z-10 overflow-visible">
                    <AnimatePresence mode="wait">
                        {step === 1 && (
                            <motion.div
                                key="step1"
                                initial={{ opacity: 0, x: -10 }}
                                animate={{ opacity: 1, x: 0 }}
                                exit={{ opacity: 0, x: 10 }}
                                transition={{ duration: 0.2 }}
                            >
                                <CardHeader className="pb-6 border-b px-8 pt-8">
                                    <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center mb-4 text-foreground">
                                        <Globe className="w-5 h-5" />
                                    </div>
                                    <CardTitle className="text-xl sm:text-2xl">{__('general.where_is_your_workspace_based')}</CardTitle>
                                    <CardDescription className="mt-1.5 leading-relaxed">{__('general.setting_your_primary_operational_location_helps_us_optimize_server_routing_localized_formatting_and_legal_compliance')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6 px-8 py-6">
                                    {/* Country Combobox Selector */}
                                    <div className="space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.country')}</label>
                                        <PremiumCombobox
                                            value={formData.country}
                                            onChange={(val) => setFormData(prev => ({ ...prev, country: String(val), city: '' }))}
                                            options={countries}
                                            placeholder={__('general.select_a_country')}
                                            searchPlaceholder="Search country..."
                                        />
                                        {errors.country && <span className="text-xs text-destructive mt-1 block">{errors.country}</span>}
                                    </div>

                                    {/* City Combobox Selector */}
                                    <div className="space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.city_operational_node')}</label>
                                        <PremiumCombobox
                                            value={formData.city}
                                            onChange={(val) => setFormData(prev => ({ ...prev, city: String(val) }))}
                                            options={uniqueCities}
                                            placeholder={__('general.select_a_city')}
                                            searchPlaceholder="Search or enter custom city..."
                                            icon={<MapPin className="w-4 h-4" />}
                                            allowCustomValue={true}
                                        />
                                        {errors.city && <span className="text-xs text-destructive mt-1 block">{errors.city}</span>}
                                    </div>
                                </CardContent>
                                <CardFooter className="border-t px-8 py-4 flex justify-end bg-muted/30">
                                    <Button onClick={nextStep} size="lg" className="h-11 px-6 rounded-xl font-medium shadow-sm">{__('general.continue_to_contact')}<ArrowRight className="w-4 h-4 ml-2" />
                                    </Button>
                                </CardFooter>
                            </motion.div>
                        )}

                        {step === 2 && (
                            <motion.div
                                key="step2"
                                initial={{ opacity: 0, x: -10 }}
                                animate={{ opacity: 1, x: 0 }}
                                exit={{ opacity: 0, x: 10 }}
                                transition={{ duration: 0.2 }}
                            >
                                <CardHeader className="pb-6 border-b px-8 pt-8">
                                    <div className="w-10 h-10 rounded-xl bg-muted flex items-center justify-center mb-4 text-foreground">
                                        <Phone className="w-5 h-5" />
                                    </div>
                                    <CardTitle className="text-xl sm:text-2xl">{__('general.communication_channels')}</CardTitle>
                                    <CardDescription className="mt-1.5 leading-relaxed">{__('general.secure_operational_communication_lines_for_transaction_notifications_2fa_alerts_and_vip_dispatch')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-5 px-8 py-6">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {/* Mobile 1 */}
                                        <div className="space-y-2">
                                            <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.primary_mobile')}<span className="text-destructive">*</span></label>
                                            <Input
                                                placeholder="+1 (555) 000-0000"
                                                value={formData.mobile_1}
                                                onChange={(e) => setFormData(prev => ({ ...prev, mobile_1: e.target.value }))}
                                                className="h-10 text-sm font-medium"
                                            />
                                            {errors.mobile_1 && <span className="text-xs text-destructive mt-1 block">{errors.mobile_1}</span>}
                                        </div>

                                        {/* Mobile 2 */}
                                        <div className="space-y-2">
                                            <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{__('general.secondary_mobile')}<span className="text-muted-foreground/70 font-normal lowercase">(optional)</span></label>
                                            <Input
                                                placeholder="+1 (555) 999-9999"
                                                value={formData.mobile_2}
                                                onChange={(e) => setFormData(prev => ({ ...prev, mobile_2: e.target.value }))}
                                                className="h-10 text-sm font-medium"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                        {/* Telegram */}
                                        <div className="space-y-2">
                                            <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center space-x-1.5">
                                                <Send className="w-3.5 h-3.5 text-blue-500" />
                                                <span>{__('general.telegram_username')}</span>
                                            </label>
                                            <div className="relative flex items-center">
                                                <span className="absolute left-3 text-sm font-medium text-muted-foreground">@</span>
                                                <Input
                                                    placeholder="username"
                                                    value={formData.telegram_username}
                                                    onChange={(e) => setFormData(prev => ({ ...prev, telegram_username: e.target.value }))}
                                                    className="h-10 text-sm font-medium pl-8"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                                <CardFooter className="border-t px-8 py-4 flex items-center justify-between bg-muted/30">
                                    <Button onClick={prevStep} variant="outline" size="lg" className="h-11 px-5 rounded-xl font-medium">
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Back
                                    </Button>
                                    <Button onClick={handleComplete} size="lg" className="h-11 px-8 rounded-xl font-medium shadow-md" disabled={saving}>
                                        {saving ? (
                                            <>
                                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />{__('general.configuring_workspace')}</>
                                        ) : (
                                            <>
                                                <Sparkles className="w-4 h-4 mr-2 text-amber-400" />{__('general.complete_setup')}</>
                                        )}
                                    </Button>
                                </CardFooter>
                            </motion.div>
                        )}

                    </AnimatePresence>
                </Card>
            </main>
        </div>
    );
}
