import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import {
    Smartphone,
    Camera,
    Trash2,
    Save,
    RefreshCw,
    Shield,
    ShieldCheck,
    CheckCircle2,
    AlertCircle,
    Info,
    Globe,
    Mail,
    MapPin,
    Building,
    FileText,
    Sparkles,
    KeyRound,
    Lock,
    ExternalLink
} from 'lucide-react';

interface Account {
    id: number;
    name: string;
    phone_number_id: string;
    waba_id: string | null;
    status: string;
    display_phone_number?: string | null;
    metadata?: any;
}

interface Business {
    id: number;
    name: string;
    wallet_balance: string;
    currency: string;
}

interface BusinessProfile {
    about?: string;
    description?: string;
    address?: string;
    email?: string;
    websites?: string[];
    vertical?: string;
    profile_picture_url?: string;
}

interface HealthData {
    verified_name?: string;
    display_phone_number?: string;
    quality_rating?: string;
    name_status?: string;
    code_verification_status?: string;
    messaging_limit_tier?: string;
}

interface Props {
    business: Business;
    accounts: Account[];
    selectedAccountId: number;
    onSelectAccount?: (id: number) => void;
}

const VERTICAL_OPTIONS = [
    { value: 'OTHER', label: 'Other (عام / أخرى)' },
    { value: 'PROF_SERVICES', label: 'Professional Services (خدمات مهنية واستشارية)' },
    { value: 'RETAIL', label: 'Retail & E-commerce (تجارة وتجزئة)' },
    { value: 'EDU', label: 'Education (تعليم وتدريب)' },
    { value: 'RESTAURANT', label: 'Food & Restaurant (مطاعم ومأكولات)' },
    { value: 'HEALTH', label: 'Health & Medical (صحة وطب)' },
    { value: 'FINANCE', label: 'Finance & Banking (مالية وبنوك)' },
    { value: 'BEAUTY', label: 'Beauty & Wellness (تجميل وعناية)' },
    { value: 'AUTO', label: 'Automotive (سيارات ومركبات)' },
    { value: 'ENTERTAIN', label: 'Entertainment (ترفيه وإعلام)' },
    { value: 'EVENT_PLAN', label: 'Event Planning (تنظيم فعاليات)' },
    { value: 'HOTEL', label: 'Hotel & Travel (فنادق وسياحة)' },
    { value: 'NONPROFIT', label: 'Non-Profit Organization (منظمة غير ربحية)' },
];

const PRESET_ABOUTS = [
    'Available for support and inquiries.',
    'Official WhatsApp Business Account.',
    'Chat with our team for instant help.',
    'متاح للرد على كافة الاستفسارات والطلبات.',
    'الحساب الرسمي للنشاط التجاري.',
];

export default function BusinessProfileTab({ business, accounts, selectedAccountId: initialAccountId, onSelectAccount }: Props) {
    const activeAccount = accounts.find(a => a.id === initialAccountId) || accounts.find(a => a.status === 'active') || accounts[0];
    const [currentAccountId, setCurrentAccountId] = useState<number>(activeAccount?.id || 0);

    const [isLoading, setIsLoading] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [isUploadingPhoto, setIsUploadingPhoto] = useState(false);
    const [isSavingPin, setIsSavingPin] = useState(false);
    const [isSyncingHealth, setIsSyncingHealth] = useState(false);

    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const [profile, setProfile] = useState<BusinessProfile>({
        about: '',
        description: '',
        address: '',
        email: '',
        websites: ['', ''],
        vertical: 'PROF_SERVICES',
        profile_picture_url: '',
    });

    const [health, setHealth] = useState<HealthData | null>(null);
    const [isSandbox, setIsSandbox] = useState(false);
    const [pinInput, setPinInput] = useState('');
    const [hasPin, setHasPin] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);

    const currentAccount = accounts.find(a => a.id === currentAccountId) || accounts[0];

    const fetchProfile = async (accId: number) => {
        if (!accId) return;
        setIsLoading(true);
        setErrorMessage(null);
        try {
            const res = await axios.get(`/whatsapp-sender/accounts/${accId}/profile`);
            if (res.data.success) {
                const p = res.data.profile || {};
                const websites = Array.isArray(p.websites) ? p.websites : [];
                setProfile({
                    about: p.about || '',
                    description: p.description || '',
                    address: p.address || '',
                    email: p.email || '',
                    websites: [websites[0] || '', websites[1] || ''],
                    vertical: p.vertical || 'PROF_SERVICES',
                    profile_picture_url: p.profile_picture_url || res.data.account?.metadata?.profile_picture_url || '',
                });
                setHealth(res.data.health || null);
                setIsSandbox(!!res.data.is_sandbox);
                setHasPin(!!res.data.account?.metadata?.has_2fa_pin);
            }
        } catch (err: any) {
            console.error('Failed to load profile:', err);
            setErrorMessage(err.response?.data?.error || 'Failed to load business profile from Meta.');
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        if (currentAccountId) {
            fetchProfile(currentAccountId);
        }
    }, [currentAccountId]);

    const handleAccountChange = (id: number) => {
        setCurrentAccountId(id);
        if (onSelectAccount) {
            onSelectAccount(id);
        }
    };

    const handleSaveProfile = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!currentAccountId) return;
        setIsSaving(true);
        setSuccessMessage(null);
        setErrorMessage(null);

        const cleanWebsites = (profile.websites || []).map(w => w.trim()).filter(Boolean);

        try {
            const res = await axios.post(`/whatsapp-sender/accounts/${currentAccountId}/profile`, {
                about: profile.about,
                description: profile.description,
                address: profile.address,
                email: profile.email,
                vertical: profile.vertical,
                websites: cleanWebsites,
            });

            if (res.data.success) {
                setSuccessMessage('Business profile updated successfully on Meta Cloud API!');
                setTimeout(() => setSuccessMessage(null), 5000);
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.error || 'Failed to save business profile.');
        } finally {
            setIsSaving(false);
        }
    };

    const handlePhotoSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file || !currentAccountId) return;

        if (file.size > 5 * 1024 * 1024) {
            setErrorMessage('Photo size exceeds maximum allowed size (5MB).');
            return;
        }

        setIsUploadingPhoto(true);
        setErrorMessage(null);
        setSuccessMessage(null);

        const formData = new FormData();
        formData.append('photo', file);

        try {
            const res = await axios.post(`/whatsapp-sender/accounts/${currentAccountId}/profile/photo`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.success) {
                setProfile(prev => ({ ...prev, profile_picture_url: res.data.profile_picture_url }));
                setSuccessMessage('WhatsApp profile picture updated successfully!');
                setTimeout(() => setSuccessMessage(null), 5000);
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.error || 'Failed to upload profile picture.');
        } finally {
            setIsUploadingPhoto(false);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    const handleDeletePhoto = async () => {
        if (!currentAccountId) return;
        if (!confirm('Are you sure you want to remove this profile picture?')) return;

        setIsUploadingPhoto(true);
        setErrorMessage(null);
        setSuccessMessage(null);

        try {
            const res = await axios.delete(`/whatsapp-sender/accounts/${currentAccountId}/profile/photo`);
            if (res.data.success) {
                setProfile(prev => ({ ...prev, profile_picture_url: '' }));
                setSuccessMessage('Profile picture removed successfully.');
                setTimeout(() => setSuccessMessage(null), 5000);
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.error || 'Failed to delete profile picture.');
        } finally {
            setIsUploadingPhoto(false);
        }
    };

    const handleSavePin = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!currentAccountId || !pinInput) return;

        if (!/^\d{6}$/.test(pinInput)) {
            setErrorMessage('PIN must be exactly 6 numeric digits.');
            return;
        }

        setIsSavingPin(true);
        setErrorMessage(null);
        setSuccessMessage(null);

        try {
            const res = await axios.post(`/whatsapp-sender/accounts/${currentAccountId}/pin`, {
                pin: pinInput,
            });

            if (res.data.success) {
                setHasPin(true);
                setPinInput('');
                setSuccessMessage('Two-Step Verification PIN has been set and verified on Meta Cloud API!');
                setTimeout(() => setSuccessMessage(null), 5000);
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.error || 'Failed to set Two-Step Verification PIN.');
        } finally {
            setIsSavingPin(false);
        }
    };

    const handleSyncHealth = async () => {
        if (!currentAccountId) return;
        setIsSyncingHealth(true);
        setErrorMessage(null);
        try {
            const res = await axios.post(`/whatsapp-sender/accounts/${currentAccountId}/health-sync`);
            if (res.data.success) {
                setHealth(res.data.health);
                setSuccessMessage('Account health and limits synchronized.');
                setTimeout(() => setSuccessMessage(null), 4000);
            }
        } catch (err: any) {
            setErrorMessage(err.response?.data?.error || 'Failed to sync health status.');
        } finally {
            setIsSyncingHealth(false);
        }
    };

    if (accounts.length === 0) {
        return (
            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-12 text-center max-w-xl mx-auto space-y-4">
                <div className="w-16 h-16 bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl flex items-center justify-center mx-auto text-emerald-600 dark:text-emerald-400">
                    <Smartphone className="w-8 h-8" />
                </div>
                <h3 className="text-xl font-bold text-zinc-900 dark:text-zinc-100">No WhatsApp Numbers Connected</h3>
                <p className="text-sm text-zinc-500">
                    You need to connect at least one WhatsApp Business Cloud API number to customize profile pictures, status/about, and business details.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-6 max-w-7xl mx-auto text-zinc-900 dark:text-zinc-100">
            {/* Header and Account Selector */}
            <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <span className="p-2.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                                <Sparkles className="w-6 h-6" />
                            </span>
                            <div>
                                <h2 className="text-xl font-bold tracking-tight">WhatsApp Business Profile</h2>
                                <p className="text-xs text-zinc-500">
                                    Manage profile picture, status/about bio, business address, email, websites, and 2FA security.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Account Switcher */}
                    <div className="flex items-center gap-3">
                        <label className="text-xs font-semibold text-zinc-500 whitespace-nowrap">Selected Number:</label>
                        <select
                            value={currentAccountId}
                            onChange={(e) => handleAccountChange(Number(e.target.value))}
                            className="bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl px-3 py-2 font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                            {accounts.map(acc => (
                                <option key={acc.id} value={acc.id}>
                                    {acc.name} {acc.display_phone_number ? `(${acc.display_phone_number})` : ''} - {acc.status.toUpperCase()}
                                </option>
                            ))}
                        </select>
                        <button
                            type="button"
                            onClick={() => fetchProfile(currentAccountId)}
                            disabled={isLoading}
                            className="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors disabled:opacity-50"
                            title="Refresh from Meta"
                        >
                            <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin text-emerald-500' : ''}`} />
                        </button>
                    </div>
                </div>

                {/* Status Badges */}
                {currentAccount && (
                    <div className="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center gap-2 text-xs">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 font-mono text-zinc-600 dark:text-zinc-300">
                            <Smartphone className="w-3.5 h-3.5 text-zinc-400" />
                            Phone ID: {currentAccount.phone_number_id}
                        </span>
                        {currentAccount.waba_id && (
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 font-mono text-zinc-600 dark:text-zinc-300">
                                WABA ID: {currentAccount.waba_id}
                            </span>
                        )}
                        <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full font-semibold ${
                            currentAccount.status === 'active'
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60'
                                : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60'
                        }`}>
                            <span className={`w-1.5 h-1.5 rounded-full ${currentAccount.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'}`} />
                            Status: {currentAccount.status.toUpperCase()}
                        </span>
                        {isSandbox && (
                            <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 font-semibold border border-purple-200 dark:border-purple-800/60">
                                Meta Sandbox / Test Mode
                            </span>
                        )}
                    </div>
                )}
            </div>

            {/* Notification Alerts */}
            {successMessage && (
                <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm flex items-center gap-3 animate-in fade-in">
                    <CheckCircle2 className="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span>{successMessage}</span>
                </div>
            )}
            {errorMessage && (
                <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm flex items-center gap-3 animate-in fade-in">
                    <AlertCircle className="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                    <span className="font-medium">{errorMessage}</span>
                </div>
            )}

            {/* Main Layout Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {/* Left Column: Avatar & Meta Health & 2FA */}
                <div className="lg:col-span-4 space-y-6">
                    {/* Profile Picture Card */}
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-5">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-zinc-500">Profile Picture</h3>
                            <Camera className="w-4 h-4 text-zinc-400" />
                        </div>

                        <div className="flex flex-col items-center text-center space-y-4">
                            <div className="relative group">
                                <div className="w-32 h-32 rounded-full ring-4 ring-zinc-100 dark:ring-zinc-800 overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shadow-inner">
                                    {profile.profile_picture_url ? (
                                        <img
                                            src={profile.profile_picture_url}
                                            alt={currentAccount?.name || 'WhatsApp Profile'}
                                            className="w-full h-full object-cover"
                                            onError={(e) => {
                                                (e.target as HTMLElement).style.display = 'none';
                                            }}
                                        />
                                    ) : (
                                        <div className="text-3xl font-black text-emerald-600 dark:text-emerald-400">
                                            {currentAccount?.name?.substring(0, 2).toUpperCase() || 'WA'}
                                        </div>
                                    )}
                                </div>

                                {isUploadingPhoto && (
                                    <div className="absolute inset-0 bg-black/60 rounded-full flex flex-col items-center justify-center text-white text-xs font-semibold gap-1">
                                        <RefreshCw className="w-6 h-6 animate-spin" />
                                        <span>Syncing...</span>
                                    </div>
                                )}
                            </div>

                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                className="hidden"
                                onChange={handlePhotoSelect}
                            />

                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => fileInputRef.current?.click()}
                                    disabled={isUploadingPhoto}
                                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl flex items-center gap-2 shadow-sm transition-all disabled:opacity-50 cursor-pointer"
                                >
                                    <Camera className="w-3.5 h-3.5" />
                                    {profile.profile_picture_url ? 'Change Photo' : 'Upload Photo'}
                                </button>

                                {profile.profile_picture_url && (
                                    <button
                                        type="button"
                                        onClick={handleDeletePhoto}
                                        disabled={isUploadingPhoto}
                                        className="p-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-xl transition-all disabled:opacity-50 cursor-pointer"
                                        title="Remove Photo"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                )}
                            </div>

                            <p className="text-[11px] text-zinc-400 max-w-xs leading-relaxed">
                                Square image (640x640 px recommended), JPEG or PNG format, max 5MB. Synchronizes directly with Meta.
                            </p>
                        </div>
                    </div>

                    {/* Phone Health & Quality Stats Card */}
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-zinc-500">Account Health</h3>
                            <button
                                type="button"
                                onClick={handleSyncHealth}
                                disabled={isSyncingHealth}
                                className="text-xs text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-semibold disabled:opacity-50 cursor-pointer"
                            >
                                <RefreshCw className={`w-3.5 h-3.5 ${isSyncingHealth ? 'animate-spin' : ''}`} />
                                Sync
                            </button>
                        </div>

                        <div className="space-y-3 text-xs">
                            <div className="flex items-center justify-between p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-800">
                                <span className="text-zinc-500 font-medium">Verified Name</span>
                                <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {health?.verified_name || currentAccount?.name || 'Not Verified'}
                                </span>
                            </div>

                            <div className="flex items-center justify-between p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-800">
                                <span className="text-zinc-500 font-medium">Quality Rating</span>
                                <span className={`px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] ${
                                    health?.quality_rating === 'GREEN'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                        : health?.quality_rating === 'YELLOW'
                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                }`}>
                                    {health?.quality_rating || 'GREEN (High)'}
                                </span>
                            </div>

                            <div className="flex items-center justify-between p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-800">
                                <span className="text-zinc-500 font-medium">Messaging Limit</span>
                                <span className="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    {health?.messaging_limit_tier?.replace('TIER_', '') || 'TIER 1K'} messages/day
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Two-Step Verification Card */}
                    <div className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="w-4 h-4 text-emerald-500" />
                                <h3 className="text-sm font-bold uppercase tracking-wider text-zinc-500">Two-Step Verification (PIN)</h3>
                            </div>
                            {hasPin && (
                                <span className="text-[10px] bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 font-semibold px-2 py-0.5 rounded-full">
                                    Protected
                                </span>
                            )}
                        </div>

                        <p className="text-xs text-zinc-500">
                            Set a 6-digit numeric PIN to protect your phone number against unauthorized registration.
                        </p>

                        <form onSubmit={handleSavePin} className="space-y-3">
                            <div className="relative">
                                <input
                                    type="password"
                                    maxLength={6}
                                    pattern="[0-9]{6}"
                                    value={pinInput}
                                    onChange={(e) => setPinInput(e.target.value.replace(/\D/g, ''))}
                                    placeholder="Enter 6-digit PIN"
                                    className="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl px-4 py-2.5 text-sm font-mono text-center tracking-widest focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={isSavingPin || pinInput.length !== 6}
                                className="w-full py-2.5 bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 text-xs font-semibold rounded-xl transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <Lock className="w-3.5 h-3.5" />
                                {isSavingPin ? 'Setting PIN...' : 'Set 6-Digit PIN'}
                            </button>
                        </form>
                    </div>
                </div>

                {/* Right Column: Business Profile Form */}
                <div className="lg:col-span-8">
                    <form onSubmit={handleSaveProfile} className="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
                        <div className="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-5">
                            <div>
                                <h3 className="text-lg font-bold">Business Information</h3>
                                <p className="text-xs text-zinc-500">
                                    Customer-facing details displayed in WhatsApp contact info.
                                </p>
                            </div>
                            <button
                                type="submit"
                                disabled={isSaving || isLoading}
                                className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-2xl flex items-center gap-2 shadow-sm transition-all disabled:opacity-50 cursor-pointer"
                            >
                                <Save className="w-4 h-4" />
                                {isSaving ? 'Saving to Meta...' : 'Save Profile'}
                            </button>
                        </div>

                        {/* About / Status */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                    <FileText className="w-3.5 h-3.5 text-zinc-400" />
                                    About / Status (الحالة)
                                </label>
                                <span className={`text-[11px] font-mono ${(profile.about?.length || 0) > 130 ? 'text-amber-500' : 'text-zinc-400'}`}>
                                    {profile.about?.length || 0} / 139 chars
                                </span>
                            </div>
                            <input
                                type="text"
                                maxLength={139}
                                value={profile.about || ''}
                                onChange={(e) => setProfile({ ...profile, about: e.target.value })}
                                placeholder="e.g. Available for 24/7 customer support"
                                className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            />
                            {/* Preset pills */}
                            <div className="flex flex-wrap gap-1.5 pt-1">
                                <span className="text-[10px] text-zinc-400 self-center">Quick suggestions:</span>
                                {PRESET_ABOUTS.map((preset, idx) => (
                                    <button
                                        key={idx}
                                        type="button"
                                        onClick={() => setProfile({ ...profile, about: preset })}
                                        className="text-[11px] px-2.5 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/40 text-zinc-600 dark:text-zinc-300 transition-colors cursor-pointer"
                                    >
                                        {preset}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Business Description */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                    <Info className="w-3.5 h-3.5 text-zinc-400" />
                                    Business Description (الوصف الكامل)
                                </label>
                                <span className={`text-[11px] font-mono ${(profile.description?.length || 0) > 500 ? 'text-amber-500' : 'text-zinc-400'}`}>
                                    {profile.description?.length || 0} / 512 chars
                                </span>
                            </div>
                            <textarea
                                rows={4}
                                maxLength={512}
                                value={profile.description || ''}
                                onChange={(e) => setProfile({ ...profile, description: e.target.value })}
                                placeholder="Describe what products, services, or solutions your business provides..."
                                className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl p-4 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none resize-none leading-relaxed"
                            />
                        </div>

                        {/* Business Category / Vertical */}
                        <div className="space-y-2">
                            <label className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <Building className="w-3.5 h-3.5 text-zinc-400" />
                                Business Category (التصنيف التجاري)
                            </label>
                            <select
                                value={profile.vertical || 'PROF_SERVICES'}
                                onChange={(e) => setProfile({ ...profile, vertical: e.target.value })}
                                className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                {VERTICAL_OPTIONS.map(opt => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Address & Email */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                    <MapPin className="w-3.5 h-3.5 text-zinc-400" />
                                    Address (العنوان)
                                </label>
                                <input
                                    type="text"
                                    maxLength={256}
                                    value={profile.address || ''}
                                    onChange={(e) => setProfile({ ...profile, address: e.target.value })}
                                    placeholder="e.g. 123 Tech Park, Cairo, Egypt"
                                    className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                    <Mail className="w-3.5 h-3.5 text-zinc-400" />
                                    Business Email (البريد الإلكتروني)
                                </label>
                                <input
                                    type="email"
                                    value={profile.email || ''}
                                    onChange={(e) => setProfile({ ...profile, email: e.target.value })}
                                    placeholder="contact@yourdomain.com"
                                    className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        {/* Websites (Up to 2) */}
                        <div className="space-y-3">
                            <label className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <Globe className="w-3.5 h-3.5 text-zinc-400" />
                                Websites (المواقع الإلكترونية - حد أقصى رابطين)
                            </label>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input
                                    type="url"
                                    value={profile.websites?.[0] || ''}
                                    onChange={(e) => {
                                        const newWebsites = [...(profile.websites || ['', ''])];
                                        newWebsites[0] = e.target.value;
                                        setProfile({ ...profile, websites: newWebsites });
                                    }}
                                    placeholder="Website 1 (https://...)"
                                    className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                                <input
                                    type="url"
                                    value={profile.websites?.[1] || ''}
                                    onChange={(e) => {
                                        const newWebsites = [...(profile.websites || ['', ''])];
                                        newWebsites[1] = e.target.value;
                                        setProfile({ ...profile, websites: newWebsites });
                                    }}
                                    placeholder="Website 2 (optional https://...)"
                                    className="w-full bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700 rounded-2xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        {/* Submit button footer */}
                        <div className="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-end">
                            <button
                                type="submit"
                                disabled={isSaving || isLoading}
                                className="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-2xl flex items-center gap-2 shadow-md hover:shadow-emerald-500/20 transition-all disabled:opacity-50 cursor-pointer"
                            >
                                <Save className="w-4 h-4" />
                                {isSaving ? 'Saving to Meta...' : 'Save Profile Changes'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
