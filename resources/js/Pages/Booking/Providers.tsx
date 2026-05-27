import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';
import { Calendar, Clock, Plus, Users, User, Mail, Phone, Stethoscope, Settings, Check, X, ShieldAlert, ArrowRight, Save, Trash2, Edit2, ToggleLeft, ToggleRight, CalendarOff } from 'lucide-react';

interface EventType {
    id: number;
    title: string;
    slug: string;
    description: string;
    duration_minutes: number;
    price: number;
    currency: string;
    requires_payment: boolean;
    is_active: boolean;
}

interface AvailabilityRule {
    id?: number;
    type: 'recurring' | 'one-time';
    weekday: number | null;
    date: string | null;
    start_time: string;
    end_time: string;
    is_enabled: boolean;
}

interface BookingProvider {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    specialty: string | null;
    description: string | null;
    is_active: boolean;
    avatar_url?: string;
    event_types: EventType[];
    availability_rules: AvailabilityRule[];
}

const WEEKDAYS = [
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 0, label: 'Sunday' },
];

export default function Providers({ providers, eventTypes }: { providers: BookingProvider[], eventTypes: EventType[] }) {
    const [activeTab, setActiveTab] = useState<'registry' | 'schedule'>('registry');
    const [selectedProvider, setSelectedProvider] = useState<BookingProvider | null>(null);
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);

    // Profile form
    const profileForm = useForm({
        id: null as number | null,
        name: '',
        email: '',
        phone: '',
        specialty: '',
        description: '',
        is_active: true,
        event_type_ids: [] as number[],
    });

    // Availability rules state
    const [weeklyRules, setWeeklyRules] = useState<Record<number, { is_enabled: boolean; shifts: Array<{ start_time: string; end_time: string }> }>>({
        1: { is_enabled: true, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
        2: { is_enabled: true, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
        3: { is_enabled: true, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
        4: { is_enabled: true, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
        5: { is_enabled: true, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
        6: { is_enabled: false, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
        0: { is_enabled: false, shifts: [{ start_time: '09:00', end_time: '17:00' }] },
    });

    const [oneTimeRules, setOneTimeRules] = useState<Array<{ date: string; start_time: string; end_time: string; is_enabled: boolean }>>([]);
    const [newOverride, setNewOverride] = useState({ date: '', start_time: '09:00', end_time: '17:00', is_enabled: true });

    // Open create provider view
    const handleAddProviderClick = () => {
        profileForm.reset();
        profileForm.clearErrors();
        setIsEditing(false);
        setIsFormOpen(true);
    };

    // Open edit provider view
    const handleEditProviderClick = (provider: BookingProvider) => {
        profileForm.clearErrors();
        profileForm.setData({
            id: provider.id,
            name: provider.name,
            email: provider.email || '',
            phone: provider.phone || '',
            specialty: provider.specialty || '',
            description: provider.description || '',
            is_active: provider.is_active,
            event_type_ids: provider.event_types.map(et => et.id),
        });
        setIsEditing(true);
        setIsFormOpen(true);
    };

    // Submit provider profile form
    const handleProfileSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing && profileForm.id) {
            profileForm.put(route('booking.providers.update', profileForm.id), {
                onSuccess: () => setIsFormOpen(false)
            });
        } else {
            profileForm.post(route('booking.providers.store'), {
                onSuccess: () => setIsFormOpen(false)
            });
        }
    };

    // Select provider and initialize their availability schedule in state
    const handleManageScheduleClick = (provider: BookingProvider) => {
        setSelectedProvider(provider);
        
        // Initialize weekly rules
        const defaultWeekly = {
            1: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
            2: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
            3: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
            4: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
            5: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
            6: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
            0: { is_enabled: false, shifts: [] as Array<{ start_time: string; end_time: string }> },
        };

        provider.availability_rules.forEach(rule => {
            if (rule.type === 'recurring' && rule.weekday !== null) {
                // Strip seconds if present e.g. "09:00:00" -> "09:00"
                const start = rule.start_time.substring(0, 5);
                const end = rule.end_time.substring(0, 5);
                defaultWeekly[rule.weekday as keyof typeof defaultWeekly].is_enabled = !!rule.is_enabled;
                defaultWeekly[rule.weekday as keyof typeof defaultWeekly].shifts.push({ start_time: start, end_time: end });
            }
        });
        
        // Ensure every day has at least one empty shift so UI doesn't break if enabled
        Object.keys(defaultWeekly).forEach(day => {
            if (defaultWeekly[day as any].shifts.length === 0) {
                defaultWeekly[day as any].shifts.push({ start_time: '09:00', end_time: '17:00' });
            }
        });
        
        setWeeklyRules(defaultWeekly);

        // Initialize one-time rules
        const customOverrides = provider.availability_rules
            .filter(rule => rule.type === 'one-time' && rule.date !== null)
            .map(rule => ({
                date: rule.date!,
                start_time: rule.start_time.substring(0, 5),
                end_time: rule.end_time.substring(0, 5),
                is_enabled: !!rule.is_enabled,
            }));
        setOneTimeRules(customOverrides);
        
        setActiveTab('schedule');
    };

    // Toggle active status directly
    const toggleProviderActive = (provider: BookingProvider) => {
        router.put(route('booking.providers.update', provider.id), {
            name: provider.name,
            specialty: provider.specialty,
            email: provider.email,
            phone: provider.phone,
            description: provider.description,
            is_active: !provider.is_active,
            event_type_ids: provider.event_types.map(et => et.id),
        }, { preserveScroll: true });
    };

    // Handle updates to weekly recurring schedules
    const handleWeeklyCheckToggle = (day: number) => {
        setWeeklyRules(prev => ({
            ...prev,
            [day]: { ...prev[day], is_enabled: !prev[day].is_enabled }
        }));
    };

    const handleWeeklyTimeChange = (day: number, shiftIndex: number, field: 'start_time' | 'end_time', value: string) => {
        setWeeklyRules(prev => {
            const newShifts = [...prev[day].shifts];
            newShifts[shiftIndex] = { ...newShifts[shiftIndex], [field]: value };
            return {
                ...prev,
                [day]: { ...prev[day], shifts: newShifts }
            };
        });
    };

    const addWeeklyShift = (day: number) => {
        setWeeklyRules(prev => ({
            ...prev,
            [day]: { ...prev[day], shifts: [...prev[day].shifts, { start_time: '09:00', end_time: '17:00' }] }
        }));
    };

    const removeWeeklyShift = (day: number, shiftIndex: number) => {
        setWeeklyRules(prev => {
            const newShifts = prev[day].shifts.filter((_, i) => i !== shiftIndex);
            // Don't remove the last shift, just leave it or disable the day
            if (newShifts.length === 0) {
                return {
                    ...prev,
                    [day]: { is_enabled: false, shifts: [{ start_time: '09:00', end_time: '17:00' }] }
                };
            }
            return {
                ...prev,
                [day]: { ...prev[day], shifts: newShifts }
            };
        });
    };

    // Handle custom overrides
    const handleAddOverride = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newOverride.date) return;
        
        // Check if override for this date already exists
        if (oneTimeRules.some(r => r.date === newOverride.date)) {
            alert('An override schedule already exists for this date.');
            return;
        }

        setOneTimeRules(prev => [...prev, { ...newOverride }].sort((a, b) => a.date.localeCompare(b.date)));
        setNewOverride({ date: '', start_time: '09:00', end_time: '17:00', is_enabled: true });
    };

    const handleRemoveOverride = (index: number) => {
        setOneTimeRules(prev => prev.filter((_, i) => i !== index));
    };

    // Save final schedules to backend
    const handleSaveSchedule = () => {
        if (!selectedProvider) return;

        const formattedRules: Array<{
            type: 'recurring' | 'one-time';
            weekday: number | null;
            date: string | null;
            start_time: string;
            end_time: string;
            is_enabled: boolean;
        }> = [];

        // 1. Gather recurring weekly rules
        Object.entries(weeklyRules).forEach(([dayStr, data]) => {
            const day = parseInt(dayStr);
            if (data.is_enabled) {
                data.shifts.forEach(shift => {
                    formattedRules.push({
                        type: 'recurring',
                        weekday: day,
                        date: null,
                        start_time: shift.start_time,
                        end_time: shift.end_time,
                        is_enabled: true,
                    });
                });
            }
        });

        // 2. Gather custom override rules
        oneTimeRules.forEach(rule => {
            formattedRules.push({
                type: 'one-time',
                weekday: null,
                date: rule.date,
                start_time: rule.start_time,
                end_time: rule.end_time,
                is_enabled: rule.is_enabled,
            });
        });

        router.post(route('booking.providers.availability', selectedProvider.id), {
            rules: formattedRules
        }, {
            onSuccess: () => {
                // Fetch the updated provider object from the fresh props
                const updated = providers.find(p => p.id === selectedProvider.id);
                if (updated) {
                    setSelectedProvider(updated);
                }
            }
        });
    };

    return (
        <WorkspaceLayout
            title="Booking Providers"
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: false },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: false },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: false },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: true },
                { id: 'exceptions', label: 'Exceptions', icon: CalendarOff, href: '/booking/exceptions', isActive: false },
            ]}
        >
            <Head title="Booking Providers & Schedules" />

            <div className="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Providers & Schedules</h1>
                        <p className="text-muted-foreground">Configure doctors, consultants, multi-host rosters, and availability calendars.</p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        {activeTab === 'registry' && !isFormOpen && (
                            <Button onClick={handleAddProviderClick} className="bg-slate-900 hover:bg-slate-800 text-white shadow-sm">
                                <Plus className="w-4 h-4 mr-2" />
                                Add Provider
                            </Button>
                        )}
                    </div>
                </div>

                <Tabs value={activeTab} onValueChange={(val) => setActiveTab(val as any)} className="w-full">
                    <TabsList className="bg-slate-100 border border-slate-200/60 p-1">
                        <TabsTrigger value="registry" className="data-[state=active]:bg-white data-[state=active]:shadow-sm">
                            <Users className="w-4 h-4 mr-2" />
                            Providers Registry
                        </TabsTrigger>
                        <TabsTrigger value="schedule" className="data-[state=active]:bg-white data-[state=active]:shadow-sm" disabled={!selectedProvider}>
                            <Calendar className="w-4 h-4 mr-2" />
                            {selectedProvider ? `${selectedProvider.name}'s Schedule` : 'Schedule Builder'}
                        </TabsTrigger>
                    </TabsList>

                    {/* TAB 1: REGISTRY AND FORMS */}
                    <TabsContent value="registry" className="mt-6">
                        {isFormOpen ? (
                            /* Add / Edit Profile View */
                            <Card className="max-w-2xl border-slate-200/80 shadow-md">
                                <CardHeader className="border-b border-slate-100 pb-4">
                                    <div className="flex justify-between items-center">
                                        <CardTitle className="text-xl font-semibold flex items-center gap-2">
                                            <User className="h-5 w-5 text-slate-800" />
                                            {isEditing ? 'Edit Provider Profile' : 'Register New Provider'}
                                        </CardTitle>
                                        <Button variant="ghost" size="sm" onClick={() => setIsFormOpen(false)} className="h-8 w-8 p-0 rounded-full">
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <CardDescription>
                                        Configure profile bio details, contact records, and match them with active client services.
                                    </CardDescription>
                                </CardHeader>
                                <form onSubmit={handleProfileSubmit}>
                                    <CardContent className="space-y-6 pt-6">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="name">Full Name</Label>
                                                <Input
                                                    id="name"
                                                    value={profileForm.name}
                                                    onChange={e => profileForm.setData('name', e.target.value)}
                                                    placeholder="Dr. John Doe"
                                                    required
                                                />
                                                {profileForm.errors.name && <p className="text-sm text-red-500">{profileForm.errors.name}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="specialty">Specialty / Title</Label>
                                                <Input
                                                    id="specialty"
                                                    value={profileForm.specialty}
                                                    onChange={e => profileForm.setData('specialty', e.target.value)}
                                                    placeholder="e.g. Cardiologist, Senior Consultant, Math Tutor"
                                                />
                                                {profileForm.errors.specialty && <p className="text-sm text-red-500">{profileForm.errors.specialty}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="email">Email Address</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    value={profileForm.email}
                                                    onChange={e => profileForm.setData('email', e.target.value)}
                                                    placeholder="john.doe@hospital.com"
                                                />
                                                {profileForm.errors.email && <p className="text-sm text-red-500">{profileForm.errors.email}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="phone">Phone Number</Label>
                                                <Input
                                                    id="phone"
                                                    value={profileForm.phone}
                                                    onChange={e => profileForm.setData('phone', e.target.value)}
                                                    placeholder="+1 (555) 123-4567"
                                                />
                                                {profileForm.errors.phone && <p className="text-sm text-red-500">{profileForm.errors.phone}</p>}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="description">About / Profile Summary</Label>
                                            <Textarea
                                                id="description"
                                                value={profileForm.description}
                                                onChange={e => profileForm.setData('description', e.target.value)}
                                                placeholder="Write a brief professional description that will be visible to booking clients..."
                                                rows={4}
                                            />
                                            {profileForm.errors.description && <p className="text-sm text-red-500">{profileForm.errors.description}</p>}
                                        </div>

                                        <div className="space-y-3">
                                            <Label className="block mb-1">Performs Services / Event Types</Label>
                                            <p className="text-xs text-muted-foreground mb-2">Select which services this provider is certified to perform.</p>
                                            {eventTypes.length === 0 ? (
                                                <div className="p-4 border rounded-lg bg-slate-50 text-center text-sm text-slate-500">
                                                    No services exist yet. Please create a Booking Event Type first.
                                                </div>
                                            ) : (
                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto p-3 border rounded-lg bg-slate-50">
                                                    {eventTypes.map(event => {
                                                        const isChecked = profileForm.event_type_ids.includes(event.id);
                                                        return (
                                                            <label key={event.id} className="flex items-center space-x-3 p-2 bg-white rounded border border-slate-200/60 shadow-xs hover:bg-slate-50 cursor-pointer">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={isChecked}
                                                                    onChange={() => {
                                                                        const ids = [...profileForm.event_type_ids];
                                                                        if (isChecked) {
                                                                            profileForm.setData('event_type_ids', ids.filter(id => id !== event.id));
                                                                        } else {
                                                                            ids.push(event.id);
                                                                            profileForm.setData('event_type_ids', ids);
                                                                        }
                                                                    }}
                                                                    className="rounded border-slate-300 text-slate-900 focus:ring-slate-500 h-4 w-4"
                                                                />
                                                                <div className="text-sm">
                                                                    <span className="font-medium text-slate-900 block leading-tight">{event.title}</span>
                                                                    <span className="text-xs text-slate-500">{event.duration_minutes} mins</span>
                                                                </div>
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>

                                        <div className="flex items-center justify-between p-4 border rounded-lg bg-slate-50/50">
                                            <div className="space-y-0.5">
                                                <Label>Active Roster Status</Label>
                                                <p className="text-xs text-muted-foreground">Inactive providers cannot be selected for public scheduling slots.</p>
                                            </div>
                                            <Switch
                                                checked={profileForm.is_active}
                                                onCheckedChange={checked => profileForm.setData('is_active', checked)}
                                            />
                                        </div>
                                    </CardContent>
                                    <CardFooter className="border-t border-slate-100 px-6 py-4 flex justify-end gap-3 bg-slate-50/50 rounded-b-xl">
                                        <Button type="button" variant="outline" onClick={() => setIsFormOpen(false)}>
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={profileForm.processing} className="bg-slate-900 text-white hover:bg-slate-800">
                                            {isEditing ? 'Save Changes' : 'Register Provider'}
                                        </Button>
                                    </CardFooter>
                                </form>
                            </Card>
                        ) : (
                            /* Roster Grid View */
                            <div>
                                {providers.length === 0 ? (
                                    <div className="border border-dashed border-slate-300 rounded-xl p-12 text-center bg-slate-50/50">
                                        <Users className="mx-auto h-12 w-12 text-slate-400 mb-4" />
                                        <h3 className="text-lg font-medium text-slate-900 mb-1">Roster is empty</h3>
                                        <p className="text-sm text-slate-500 max-w-md mx-auto mb-6">
                                            Register staff members, doctors, or practitioners to enable multi-host booking flows.
                                        </p>
                                        <Button onClick={handleAddProviderClick} className="bg-slate-900 hover:bg-slate-800 text-white">
                                            <Plus className="w-4 h-4 mr-2" />
                                            Register First Provider
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {providers.map(provider => (
                                            <Card key={provider.id} className={`overflow-hidden border-slate-200 shadow-sm transition-all hover:shadow-md flex flex-col ${!provider.is_active ? 'opacity-70 bg-slate-50/50' : 'bg-white'}`}>
                                                <CardContent className="p-6 flex-1">
                                                    <div className="flex justify-between items-start gap-3">
                                                        <div className="flex items-center gap-3">
                                                            <div className="h-12 w-12 rounded-full bg-slate-900 flex items-center justify-center text-white font-semibold text-lg shadow-sm">
                                                                {provider.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                                                            </div>
                                                            <div>
                                                                <h4 className="font-semibold text-slate-900 flex items-center gap-1.5 leading-snug">
                                                                    {provider.name}
                                                                </h4>
                                                                <p className="text-xs font-medium text-slate-700 flex items-center mt-0.5">
                                                                    <Stethoscope className="w-3.5 h-3.5 mr-1" />
                                                                    {provider.specialty || 'General Practitioner'}
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <button 
                                                            onClick={() => toggleProviderActive(provider)}
                                                            className={`touch-target p-1 rounded-md transition-colors flex items-center justify-center ${provider.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100'}`}
                                                            title={provider.is_active ? 'Set Inactive' : 'Set Active'}
                                                        >
                                                            {provider.is_active ? <ToggleRight className="w-7 h-7" /> : <ToggleLeft className="w-7 h-7" />}
                                                        </button>
                                                    </div>

                                                    <div className="mt-4 space-y-2 text-sm text-slate-600 border-t border-slate-100 pt-3">
                                                        {provider.email && (
                                                            <div className="flex items-center text-xs">
                                                                <Mail className="w-3.5 h-3.5 mr-2 text-slate-400 shrink-0" />
                                                                <span className="truncate">{provider.email}</span>
                                                            </div>
                                                        )}
                                                        {provider.phone && (
                                                            <div className="flex items-center text-xs">
                                                                <Phone className="w-3.5 h-3.5 mr-2 text-slate-400 shrink-0" />
                                                                <span>{provider.phone}</span>
                                                            </div>
                                                        )}
                                                    </div>

                                                    {provider.description && (
                                                        <p className="text-xs text-slate-500 line-clamp-2 mt-3 bg-slate-50 p-2 rounded border border-slate-100/50">
                                                            {provider.description}
                                                        </p>
                                                    )}

                                                    <div className="mt-4">
                                                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Assigned Services</span>
                                                        <div className="flex flex-wrap gap-1">
                                                            {provider.event_types.length === 0 ? (
                                                                <span className="text-xs text-slate-400 italic">No services assigned</span>
                                                            ) : (
                                                                provider.event_types.map(et => (
                                                                    <Badge key={et.id} variant="outline" className="text-xs px-2 py-0.5 bg-slate-50 text-slate-600 border-slate-200">
                                                                        {et.title}
                                                                    </Badge>
                                                                ))
                                                            )}
                                                        </div>
                                                    </div>
                                                </CardContent>

                                                <CardFooter className="bg-slate-50 border-t border-slate-100 p-4 flex gap-2">
                                                    <Button 
                                                        variant="outline" 
                                                        size="sm" 
                                                        onClick={() => handleEditProviderClick(provider)} 
                                                        className="flex-1 text-slate-700 bg-white border-slate-200"
                                                    >
                                                        <Edit2 className="w-3.5 h-3.5 mr-1.5" />
                                                        Edit Profile
                                                    </Button>
                                                    <Button 
                                                        size="sm" 
                                                        onClick={() => handleManageScheduleClick(provider)}
                                                        className="flex-1 bg-slate-900 hover:bg-slate-800 text-white"
                                                    >
                                                        <Calendar className="w-3.5 h-3.5 mr-1.5" />
                                                        Schedules
                                                        <ArrowRight className="w-3 h-3 ml-1.5" />
                                                    </Button>
                                                </CardFooter>
                                            </Card>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </TabsContent>

                    {/* TAB 2: SCHEDULE BUILDER */}
                    <TabsContent value="schedule" className="mt-6">
                        {selectedProvider && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                                {/* Left column: Weekly recurring shifts */}
                                <div className="lg:col-span-2 space-y-6">
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Recurring Weekly Schedules</CardTitle>
                                            <CardDescription>Specify recurring weekly shifts that repeat indefinitely. Enabled days are rendered as dynamic booking windows.</CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                        <div className="space-y-4">
                                            {WEEKDAYS.map(({ value, label }) => {
                                                const dayRule = weeklyRules[value];
                                                return (
                                                    <div key={value} className={`flex flex-col sm:flex-row sm:items-center justify-between p-4 border rounded-xl transition-all ${dayRule.is_enabled ? 'bg-white border-slate-200 shadow-xs' : 'bg-slate-50 border-slate-200 opacity-60'}`}>
                                                        <div className="flex items-center gap-3">
                                                            <input
                                                                id={`day-chk-${value}`}
                                                                type="checkbox"
                                                                checked={dayRule.is_enabled}
                                                                onChange={() => handleWeeklyCheckToggle(value)}
                                                                className="rounded border-slate-300 text-slate-900 focus:ring-slate-500 h-4.5 w-4.5 shrink-0"
                                                            />
                                                            <label htmlFor={`day-chk-${value}`} className="font-semibold text-slate-800 cursor-pointer min-w-28 text-sm sm:text-base select-none">
                                                                {label}
                                                            </label>
                                                        </div>

                                                        {dayRule.is_enabled ? (
                                                            <div className="flex flex-col gap-2 mt-3 sm:mt-0">
                                                                {dayRule.shifts.map((shift, shiftIndex) => (
                                                                    <div key={shiftIndex} className="flex items-center gap-2">
                                                                        <span className="text-xs text-slate-400 font-medium">Work hours:</span>
                                                                        <Input
                                                                            type="time"
                                                                            value={shift.start_time}
                                                                            onChange={e => handleWeeklyTimeChange(value, shiftIndex, 'start_time', e.target.value)}
                                                                            className="w-28 text-sm text-slate-700 bg-white"
                                                                        />
                                                                        <span className="text-slate-400 text-sm">to</span>
                                                                        <Input
                                                                            type="time"
                                                                            value={shift.end_time}
                                                                            onChange={e => handleWeeklyTimeChange(value, shiftIndex, 'end_time', e.target.value)}
                                                                            className="w-28 text-sm text-slate-700 bg-white"
                                                                        />
                                                                        <Button 
                                                                            type="button" 
                                                                            variant="ghost" 
                                                                            size="icon"
                                                                            onClick={() => removeWeeklyShift(value, shiftIndex)}
                                                                            className="text-red-500 hover:text-red-700 hover:bg-red-50 h-8 w-8"
                                                                        >
                                                                            <Trash2 className="w-4 h-4" />
                                                                        </Button>
                                                                    </div>
                                                                ))}
                                                                <Button 
                                                                    type="button" 
                                                                    variant="ghost" 
                                                                    onClick={() => addWeeklyShift(value)}
                                                                    className="text-xs text-slate-700 hover:text-slate-900 hover:bg-slate-100 mt-1 w-max h-7 px-2"
                                                                >
                                                                    <Plus className="w-3 h-3 mr-1" /> Add Shift
                                                                </Button>
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-2 sm:mt-0">Unavailable / Off</span>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        <div className="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5">
                                            <Button variant="outline" onClick={() => setActiveTab('registry')}>
                                                Back to Roster
                                            </Button>
                                            <Button onClick={handleSaveSchedule} className="bg-slate-900 text-white hover:bg-slate-800">
                                                <Save className="w-4 h-4 mr-2" />
                                                Save All Schedules
                                            </Button>
                                        </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Right column: Overrides and holidays */}
                                <div className="space-y-6">
                                    {/* Add Override Form */}
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Custom Override Date</CardTitle>
                                            <CardDescription>Configure vacation periods, holidays, or temporary schedule shifts. Set custom hours or uncheck enabled to block completely.</CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                        <form onSubmit={handleAddOverride} className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="override-date">Target Date</Label>
                                                <Input
                                                    id="override-date"
                                                    type="date"
                                                    value={newOverride.date}
                                                    onChange={e => setNewOverride(prev => ({ ...prev, date: e.target.value }))}
                                                    required
                                                    className="w-full bg-white"
                                                />
                                            </div>

                                            <div className="flex items-center justify-between p-3 border rounded-lg bg-slate-50/50">
                                                <div className="space-y-0.5">
                                                    <span className="text-xs font-semibold text-slate-800 block">Available on this date?</span>
                                                    <span className="text-[10px] text-slate-400 block">Disable to mark as a blocked holiday/day off.</span>
                                                </div>
                                                <Switch
                                                    checked={newOverride.is_enabled}
                                                    onCheckedChange={checked => setNewOverride(prev => ({ ...prev, is_enabled: checked }))}
                                                />
                                            </div>

                                            {newOverride.is_enabled && (
                                                <div className="grid grid-cols-2 gap-3">
                                                    <div className="space-y-1">
                                                        <Label className="text-xs text-slate-500">From</Label>
                                                        <Input
                                                            type="time"
                                                            value={newOverride.start_time}
                                                            onChange={e => setNewOverride(prev => ({ ...prev, start_time: e.target.value }))}
                                                            required
                                                            className="w-full text-sm bg-white"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs text-slate-500">To</Label>
                                                        <Input
                                                            type="time"
                                                            value={newOverride.end_time}
                                                            onChange={e => setNewOverride(prev => ({ ...prev, end_time: e.target.value }))}
                                                            required
                                                            className="w-full text-sm bg-white"
                                                        />
                                                    </div>
                                                </div>
                                            )}

                                            <Button type="submit" variant="outline" className="w-full border-slate-200 text-slate-900 bg-slate-50/50 hover:bg-slate-100">
                                                <Plus className="w-4 h-4 mr-2" /> Add Date Override
                                            </Button>
                                        </form>
                                        </CardContent>
                                    </Card>

                                    {/* Overrides List */}
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Configured Overrides</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                        {oneTimeRules.length === 0 ? (
                                            <div className="text-center py-6 text-xs text-slate-400 italic">
                                                No date overrides defined. The weekly schedule will apply consistently.
                                            </div>
                                        ) : (
                                            <div className="space-y-3 max-h-96 overflow-y-auto">
                                                {oneTimeRules.map((rule, idx) => (
                                                    <div key={idx} className={`p-3 border rounded-lg flex items-center justify-between text-sm ${rule.is_enabled ? 'bg-white border-slate-200' : 'bg-red-50/50 border-red-200'}`}>
                                                        <div>
                                                            <div className="font-semibold text-slate-800">{rule.date}</div>
                                                            <div className="text-xs text-slate-500 mt-0.5">
                                                                {rule.is_enabled ? (
                                                                    <span className="flex items-center gap-1 text-emerald-600 font-medium">
                                                                        <Check className="w-3 h-3" />
                                                                        Custom Hours: {rule.start_time} - {rule.end_time}
                                                                    </span>
                                                                ) : (
                                                                    <span className="flex items-center gap-1 text-red-600 font-medium">
                                                                        <X className="w-3 h-3" />
                                                                        Blocked Day (Holiday)
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            onClick={() => handleRemoveOverride(idx)} 
                                                            className="h-8 w-8 p-0 text-slate-400 hover:text-red-600 hover:bg-red-50"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </WorkspaceLayout>
    );
}
