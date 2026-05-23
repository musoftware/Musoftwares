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

interface UserData {
    id: number;
    name: string;
    email: string;
    country: string;
    city: string;
    mobile_1: string;
    mobile_2: string;
    telegram_username: string;
    whatsapp_number: string;
    preferred_currency: string;
    preferred_currency_locked_at: string | null;
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
        whatsapp_number: user.whatsapp_number || '',
        preferred_currency: user.preferred_currency || 'USD',
    });

    const [countrySearch, setCountrySearch] = useState('');
    const [isCountryOpen, setIsCountryOpen] = useState(false);

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved'>('idle');
    const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Dynamic cities
    const [cities, setCities] = useState<string[]>([]);
    const [isLoadingCities, setIsLoadingCities] = useState(false);
    const [citySearch, setCitySearch] = useState('');
    const [isCityOpen, setIsCityOpen] = useState(false);

    const isCurrencyLocked = !!user.preferred_currency_locked_at;

    useEffect(() => {
        if (!formData.country) return;

        let isMounted = true;
        setIsLoadingCities(true);

        axios.get(route('onboarding.cities', { countryName: formData.country }))
            .then(res => {
                if (isMounted) {
                    setCities(res.data || []);
                    setIsLoadingCities(false);
                }
            })
            .catch(err => {
                console.error('Failed to fetch cities:', err);
                if (isMounted) {
                    setIsLoadingCities(false);
                }
            });

        return () => { isMounted = false; };
    }, [formData.country]);

    // Auto-select first city if empty or country changes
    useEffect(() => {
        if (!formData.city && cities.length > 0) {
            setFormData(prev => ({ ...prev, city: cities[0] }));
        }
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
            if (!formData.preferred_currency) newErrors.preferred_currency = 'Currency selection is required.';
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

    const filteredCountries = (countries || []).filter(c => typeof c === 'string' && c.toLowerCase().includes((countrySearch || '').toLowerCase()));

    // Default top 8 cities if empty
    const defaultCities = ['New York', 'San Francisco', 'Los Angeles', 'Chicago', 'Austin', 'Miami', 'Seattle', 'Denver'];
    const currentCitiesList = cities.length > 0 ? cities : defaultCities;

    // For the custom dropdown
    const filteredCities = currentCitiesList.filter(c => c.toLowerCase().includes(citySearch.toLowerCase()));
    // Take first 20 to avoid massive DOM
    const visibleCities = filteredCities.slice(0, 20);

    return (
        <div className="min-h-screen bg-muted/20 text-foreground flex flex-col font-sans">
            {/* Top Minimal Header */}
            <header className="border-b bg-background/50 backdrop-blur-md px-6 py-4 flex items-center justify-between sticky top-0 z-50">
                <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 rounded-lg bg-primary text-primary-foreground flex items-center justify-center shadow-sm">
                        <ApplicationLogo className="w-4 h-4 fill-current" />
                    </div>
                    <span className="font-semibold text-sm tracking-tight">Workspace Onboarding</span>
                </div>

                <div className="flex items-center space-x-4 text-xs text-muted-foreground">
                    <span className="hidden sm:inline-block">Logged in as <strong className="text-foreground">{user.email}</strong></span>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Save & Exit
                    </Link>
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
                                    <CardTitle className="text-xl sm:text-2xl">Where is your workspace based?</CardTitle>
                                    <CardDescription className="mt-1.5 leading-relaxed">
                                        Setting your primary operational location helps us optimize server routing, localized formatting, and legal compliance.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6 px-8 py-6">
                                    {/* Country Combobox Selector */}
                                    <div className="space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Country</label>
                                        <div className="relative">
                                            <div
                                                onClick={() => setIsCountryOpen(!isCountryOpen)}
                                                className="w-full min-h-11 px-3.5 py-2.5 rounded-xl border bg-background hover:bg-muted/50 transition cursor-pointer flex items-center justify-between text-sm shadow-sm font-medium"
                                            >
                                                <span>{formData.country || 'Select a country...'}</span>
                                                <ChevronRight className={`w-4 h-4 text-muted-foreground transition-transform ${isCountryOpen ? 'rotate-90' : ''}`} />
                                            </div>

                                            {isCountryOpen && (
                                                <div className="absolute top-full left-0 right-0 mt-2 bg-background border rounded-xl shadow-xl z-50 p-2 max-h-60 overflow-y-auto">
                                                    <div className="relative mb-2">
                                                        <Search className="absolute left-3 top-2.5 w-4 h-4 text-muted-foreground" />
                                                        <input
                                                            type="text"
                                                            placeholder="Search country..."
                                                            value={countrySearch}
                                                            onChange={(e) => setCountrySearch(e.target.value)}
                                                            onClick={(e) => e.stopPropagation()}
                                                            className="w-full bg-muted rounded-lg pl-9 pr-3 py-1.5 text-xs border-none outline-none focus:ring-2 focus:ring-primary/20"
                                                        />
                                                    </div>
                                                    {filteredCountries.map(c => (
                                                        <div
                                                            key={c}
                                                            onClick={() => {
                                                                setFormData(prev => ({ ...prev, country: c, city: '' }));
                                                                setIsCountryOpen(false);
                                                                setCountrySearch('');
                                                            }}
                                                            className={`px-3 py-2 rounded-lg text-xs font-medium cursor-pointer transition ${c === formData.country ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted'}`}
                                                        >
                                                            {c}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                        {errors.country && <span className="text-xs text-destructive mt-1 block">{errors.country}</span>}
                                    </div>

                                    {/* City Combobox Selector */}
                                    <div className="space-y-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">City / Operational Node</label>
                                        <div className="relative">
                                            <div
                                                onClick={() => setIsCityOpen(!isCityOpen)}
                                                className="w-full min-h-11 px-3.5 py-2.5 rounded-xl border bg-background hover:bg-muted/50 transition cursor-pointer flex items-center justify-between text-sm shadow-sm font-medium"
                                            >
                                                <div className="flex items-center space-x-2 truncate">
                                                    <MapPin className="w-4 h-4 shrink-0 opacity-70" />
                                                    <span className="truncate">{formData.city || 'Select a city...'}</span>
                                                </div>
                                                {isLoadingCities ? (
                                                    <Loader2 className="w-4 h-4 text-muted-foreground animate-spin shrink-0" />
                                                ) : (
                                                    <ChevronRight className={`w-4 h-4 text-muted-foreground transition-transform shrink-0 ${isCityOpen ? 'rotate-90' : ''}`} />
                                                )}
                                            </div>

                                            {isCityOpen && (
                                                <div className="absolute top-full left-0 right-0 mt-2 bg-background border rounded-xl shadow-xl z-50 p-2 max-h-60 overflow-y-auto">
                                                    <div className="relative mb-2">
                                                        <Search className="absolute left-3 top-2.5 w-4 h-4 text-muted-foreground" />
                                                        <input
                                                            type="text"
                                                            placeholder="Search or enter custom city..."
                                                            value={citySearch}
                                                            onChange={(e) => {
                                                                setCitySearch(e.target.value);
                                                                setFormData(prev => ({ ...prev, city: e.target.value }));
                                                            }}
                                                            onClick={(e) => e.stopPropagation()}
                                                            className="w-full bg-muted rounded-lg pl-9 pr-3 py-1.5 text-xs border-none outline-none focus:ring-2 focus:ring-primary/20"
                                                        />
                                                    </div>
                                                    {visibleCities.length > 0 ? visibleCities.map(city => (
                                                        <div
                                                            key={city}
                                                            onClick={() => {
                                                                setFormData(prev => ({ ...prev, city }));
                                                                setIsCityOpen(false);
                                                                setCitySearch('');
                                                            }}
                                                            className={`px-3 py-2 rounded-lg text-xs font-medium cursor-pointer transition flex items-center space-x-2 ${city === formData.city ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted'}`}
                                                        >
                                                            <MapPin className="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                            <span className="truncate">{city}</span>
                                                        </div>
                                                    )) : (
                                                        <div className="px-3 py-4 text-xs text-center text-muted-foreground">
                                                            No cities found. Type above to use custom.
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                        {errors.city && <span className="text-xs text-destructive mt-1 block">{errors.city}</span>}
                                    </div>
                                </CardContent>
                                <CardFooter className="border-t px-8 py-4 flex justify-end bg-muted/30">
                                    <Button onClick={nextStep} size="lg" className="h-11 px-6 rounded-xl font-medium shadow-sm">
                                        Continue to Contact
                                        <ArrowRight className="w-4 h-4 ml-2" />
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
                                    <CardTitle className="text-xl sm:text-2xl">Communication Channels</CardTitle>
                                    <CardDescription className="mt-1.5 leading-relaxed">
                                        Secure operational communication lines for transaction notifications, 2FA alerts, and VIP dispatch.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-5 px-8 py-6">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {/* Mobile 1 */}
                                        <div className="space-y-2">
                                            <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Primary Mobile <span className="text-destructive">*</span></label>
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
                                            <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Secondary Mobile <span className="text-muted-foreground/70 font-normal lowercase">(optional)</span></label>
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
                                                <span>Telegram Username</span>
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

                                        {/* WhatsApp */}
                                        <div className="space-y-2">
                                            <label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center space-x-1.5">
                                                <MessageSquare className="w-3.5 h-3.5 text-emerald-500" />
                                                <span>WhatsApp Number</span>
                                            </label>
                                            <Input
                                                placeholder="+1 (555) 123-4567"
                                                value={formData.whatsapp_number}
                                                onChange={(e) => setFormData(prev => ({ ...prev, whatsapp_number: e.target.value }))}
                                                className="h-10 text-sm font-medium"
                                            />
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
                                                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                Configuring Workspace...
                                            </>
                                        ) : (
                                            <>
                                                <Sparkles className="w-4 h-4 mr-2 text-amber-400" />
                                                Complete Setup
                                            </>
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
