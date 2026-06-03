import React, { useState } from 'react';

import CrmLayout from '@/Layouts/CrmLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { __ } from '@/lib/i18n';
import {
    Users, UserPlus, X, Loader2, Lock, Unlock,
    Shield, Key, AlertCircle, Briefcase,
    MoreHorizontal, Edit2, Trash2, Phone, Megaphone,
    HeadphonesIcon, BarChart3, UserCog
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';

interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    status: 'active' | 'suspended';
    invited_at: string;
    last_login_at: string;
}

interface Translations {
    team_members: string;
    team_members_desc: string;
    seats_used: string;
    upgrade_capacity: string;
    invite_member: string;
    team_login_info: string;
    full_name: string;
    email_address: string;
    temporary_password: string;
    access_role: string;
    account_status: string;
    basic_roles: string;
    advanced_roles: string;
    advanced_roles_locked: string;
    advanced_roles_unlock: string;
    cancel: string;
    send_invite: string;
    save_changes: string;
    update_member_details: string;
    remove_member: string;
    remove_team_member: string;
    no_team_members_yet: string;
    no_team_members_desc: string;
    last_login_at: string;
    status_active: string;
    status_suspended: string;
    active_access_allowed: string;
    suspended_access_blocked: string;
    capacity_limit_warning: string;
    role_sales_manager: string;
}

interface MembersProps {
    members: TeamMember[];
    hasFeature: boolean;
    capacityLimit: number;
    activeMembersCount: number;
    hasAdvancedRolesAddon: boolean;
    basicRoles: Record<string, string>;
    advancedRoles: Record<string, string>;
    loginUrl: string;
    translations: Translations;
}

const roleIcons: Record<string, React.ElementType> = {
    'member': Users,
    'sales_agent': Phone,
    'social_media': Megaphone,
    'support_agent': HeadphonesIcon,
    'support_manager': HeadphonesIcon,
    'sales_manager': BarChart3,
    'manager': UserCog,
    'admin': Shield,
    'marketing': Megaphone,
    'call_center': Phone,
};

const roleColors: Record<string, string> = {
    'member': 'bg-slate-100 text-slate-700 border-slate-200',
    'sales_agent': 'bg-blue-50 text-blue-700 border-blue-200',
    'social_media': 'bg-pink-50 text-pink-700 border-pink-200',
    'support_agent': 'bg-amber-50 text-amber-700 border-amber-200',
    'support_manager': 'bg-amber-50 text-amber-700 border-amber-200',
    'sales_manager': 'bg-indigo-50 text-indigo-700 border-indigo-200',
    'manager': 'bg-violet-50 text-violet-700 border-violet-200',
    'admin': 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'marketing': 'bg-pink-50 text-pink-700 border-pink-200',
    'call_center': 'bg-blue-50 text-blue-700 border-blue-200',
};

export default function Members({ members, hasFeature, capacityLimit, activeMembersCount, hasAdvancedRolesAddon, basicRoles, advancedRoles, loginUrl, translations: t }: MembersProps) {
    const [showInviteModal, setShowInviteModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [selectedMember, setSelectedMember] = useState<TeamMember | null>(null);
    const [deleteConfirm, setDeleteConfirm] = useState<{ open: boolean; member: TeamMember | null }>({
        open: false,
        member: null,
    });

    const inviteForm = useForm({
        name: '',
        email: '',
        password: '',
        role: 'sales_agent',
    });

    const editForm = useForm({
        role: '',
        status: 'active' as 'active' | 'suspended',
    });

    const handleInviteSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        inviteForm.post(route('crm.team-members.store'), {
            onSuccess: () => {
                setShowInviteModal(false);
                inviteForm.reset();
            },
        });
    };

    const handleEditClick = (member: TeamMember) => {
        setSelectedMember(member);
        editForm.setData({
            role: member.role,
            status: member.status,
        });
        setShowEditModal(true);
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedMember) return;

        editForm.put(route('crm.team-members.update', selectedMember.id), {
            onSuccess: () => {
                setShowEditModal(false);
                setSelectedMember(null);
            },
        });
    };

    const handleDeleteClick = (member: TeamMember) => {
        setDeleteConfirm({ open: true, member });
    };

    const confirmDelete = () => {
        if (!deleteConfirm.member) return;

        inviteForm.delete(route('crm.team-members.destroy', deleteConfirm.member.id), {
            onSuccess: () => {
                setDeleteConfirm({ open: false, member: null });
            },
        });
    };

    return (
        <CrmLayout title={t.team_members} activeMenu="team">
            <Head title={t.team_members} />

            <div className="space-y-6">
                {!hasFeature ? (
                    <UpgradeOverlay
                        title={t.team_members}
                        description={t.team_members_desc}
                        icon={Users}
                        module="erp-team-members"
                        priceText="Unlock Team Members"
                        features={[
                            { icon: Shield, text: t.role_sales_manager },
                            { icon: UserPlus, text: t.invite_member },
                            { icon: Lock, text: t.status_suspended }
                        ]}
                    />
                ) : (
                    <>
                        {/* Header */}
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{t.team_members}</h2>
                                <p className="text-sm text-slate-500 mt-1">
                                    {t.team_members_desc}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="text-[13px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1.5 rounded-full shadow-sm flex items-center gap-2">
                                    <Users className="w-4 h-4 text-slate-400" />
                                    <span>
                                        <span className={activeMembersCount >= capacityLimit ? "text-rose-600" : "text-indigo-600"}>
                                            {activeMembersCount}
                                        </span>
                                        {' '} / {capacityLimit} {t.seats_used}
                                    </span>
                                </div>
                                {activeMembersCount >= capacityLimit ? (
                                    <Button size="sm" variant="outline" className="shadow-sm border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 flex items-center gap-1.5 cursor-not-allowed">
                                        <Lock className="h-3 w-3" /> {t.upgrade_capacity}
                                    </Button>
                                ) : (
                                    <Button
                                        size="sm"
                                        onClick={() => setShowInviteModal(true)}
                                        className="shadow-sm bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                                    >
                                        <UserPlus className="h-4 w-4" /> {t.invite_member}
                                    </Button>
                                )}
                            </div>
                        </div>

                        {/* Login URL Info */}
                        <div className="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-sm text-indigo-700 flex items-center gap-2">
                            <Key className="w-4 h-4 shrink-0" />
                            <span>{t.team_login_info} <a href={loginUrl} target="_blank" className="font-bold hover:underline" rel="noreferrer">{loginUrl}</a></span>
                        </div>

                        {/* Members Table */}
                        <OperationalCard>
                            {/* Desktop Table */}
                            <div className="hidden md:block overflow-x-auto">
                                <table className="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                            <th className="px-6 py-3.5">{t.full_name}</th>
                                            <th className="px-6 py-3.5">{t.email_address}</th>
                                            <th className="px-6 py-3.5">{t.access_role}</th>
                                            <th className="px-6 py-3.5">{t.account_status}</th>
                                            <th className="px-6 py-3.5 text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {members.length === 0 ? (
                                            <tr>
                                                <td colSpan={5} className="p-0">
                                                    <div className="flex flex-col items-center justify-center p-12 text-center">
                                                        <Users className="h-12 w-12 text-slate-300 mb-4" />
                                                        <h3 className="font-semibold text-slate-800 text-sm">{t.no_team_members_yet}</h3>
                                                        <p className="text-xs text-slate-500 max-w-xs mt-1">
                                                            {t.no_team_members_desc}
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        ) : (
                                            members.map((member) => {
                                                const RoleIcon = roleIcons[member.role] || Briefcase;
                                                const roleColor = roleColors[member.role] || 'bg-slate-100 text-slate-700 border-slate-200';
                                                return (
                                                    <tr key={member.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                                        <td className="px-6 py-4">
                                                            <div>
                                                                <span className="font-semibold text-slate-900">{member.name}</span>
                                                                <p className="text-[11px] text-slate-400 mt-0.5">{t.last_login_at}: {member.last_login_at}</p>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 font-mono text-xs text-slate-600">
                                                            {member.email}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider border ${roleColor}`}>
                                                                <RoleIcon className="h-3 w-3" />
                                                                {member.role_label}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider ${
                                                                member.status === 'active'
                                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                                                                    : 'bg-rose-50 text-rose-700 border border-rose-100'
                                                            }`}>
                                                                {member.status === 'active' ? <Unlock className="h-3 w-3" /> : <Lock className="h-3 w-3" />}
                                                                {member.status === 'active' ? t.status_active : t.status_suspended}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 text-right">
                                                            <Dialog>
                                                                <DialogTrigger asChild>
                                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                                        <span className="sr-only">{__('general.open_menu')}</span>
                                                                        <MoreHorizontal className="h-4 w-4" />
                                                                    </Button>
                                                                </DialogTrigger>
                                                                <DialogContent className="sm:max-w-xs">
                                                                    <DialogHeader>
                                                                        <DialogTitle>{member.name}</DialogTitle>
                                                                    </DialogHeader>
                                                                    <div className="flex flex-col gap-2 py-2">
                                                                        <Button variant="outline" className="justify-start" onClick={() => handleEditClick(member)}>
                                                                            <Edit2 className="h-4 w-4 mr-2" /> {t.update_member_details}
                                                                        </Button>
                                                                        <Button variant="destructive" className="justify-start" onClick={() => handleDeleteClick(member)}>
                                                                            <Trash2 className="h-4 w-4 mr-2" /> {t.remove_member}
                                                                        </Button>
                                                                    </div>
                                                                </DialogContent>
                                                            </Dialog>
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Mobile Card List */}
                            <div className="md:hidden divide-y divide-slate-100">
                                {members.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center p-8 text-center">
                                        <Users className="h-10 w-10 text-slate-300 mb-3" />
                                        <h3 className="font-semibold text-slate-800 text-sm">{t.no_team_members_yet}</h3>
                                        <p className="text-xs text-slate-500 max-w-xs mt-1">
                                            {t.no_team_members_desc}
                                        </p>
                                    </div>
                                ) : (
                                    members.map((member) => {
                                        const RoleIcon = roleIcons[member.role] || Briefcase;
                                        const roleColor = roleColors[member.role] || 'bg-slate-100 text-slate-700 border-slate-200';
                                        return (
                                            <div key={member.id} className="p-4 flex items-start justify-between gap-3">
                                                <div className="space-y-2 flex-1 min-w-0">
                                                    <div>
                                                        <p className="font-semibold text-sm text-slate-900 truncate">{member.name}</p>
                                                        <p className="text-xs text-slate-500 font-mono truncate">{member.email}</p>
                                                    </div>
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border ${roleColor}`}>
                                                            <RoleIcon className="h-3 w-3" />
                                                            {member.role_label}
                                                        </span>
                                                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold ${
                                                            member.status === 'active'
                                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                                                                : 'bg-rose-50 text-rose-700 border border-rose-100'
                                                        }`}>
                                                            {member.status === 'active' ? t.status_active : t.status_suspended}
                                                        </span>
                                                    </div>
                                                </div>
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 shrink-0">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent className="sm:max-w-xs">
                                                        <DialogHeader>
                                                            <DialogTitle>{member.name}</DialogTitle>
                                                        </DialogHeader>
                                                        <div className="flex flex-col gap-2 py-2">
                                                            <Button variant="outline" className="justify-start" onClick={() => handleEditClick(member)}>
                                                                <Edit2 className="h-4 w-4 mr-2" /> {t.update_member_details}
                                                            </Button>
                                                            <Button variant="destructive" className="justify-start" onClick={() => handleDeleteClick(member)}>
                                                                <Trash2 className="h-4 w-4 mr-2" /> {t.remove_member}
                                                            </Button>
                                                        </div>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                        </OperationalCard>

                        {/* INVITE TEAM MEMBER MODAL */}
                        {showInviteModal && (
                            <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                                <OperationalCard className="w-full max-w-md shadow-2xl animate-scale-up">
                                    <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                        <h3 className="font-semibold text-slate-800 text-[14px]">{t.invite_member}</h3>
                                        <button onClick={() => setShowInviteModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                            <X className="h-4 w-4" />
                                        </button>
                                    </div>
                                    <form onSubmit={handleInviteSubmit} className="p-6 space-y-4">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{t.full_name}</label>
                                            <Input
                                                required
                                                placeholder={__('general.ahmed_mohamed')}
                                                value={inviteForm.data.name}
                                                onChange={e => inviteForm.setData('name', e.target.value)}
                                                className="shadow-none"
                                            />
                                            {inviteForm.errors.name && <p className="text-xs text-rose-500">{inviteForm.errors.name}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{t.email_address}</label>
                                            <Input
                                                required
                                                type="email"
                                                placeholder={__('general.ahmed_example_com')}
                                                value={inviteForm.data.email}
                                                onChange={e => inviteForm.setData('email', e.target.value)}
                                                className="shadow-none"
                                            />
                                            {inviteForm.errors.email && <p className="text-xs text-rose-500">{inviteForm.errors.email}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{t.temporary_password}</label>
                                            <Input
                                                required
                                                type="password"
                                                placeholder="••••••••"
                                                value={inviteForm.data.password}
                                                onChange={e => inviteForm.setData('password', e.target.value)}
                                                className="shadow-none"
                                            />
                                            {inviteForm.errors.password && <p className="text-xs text-rose-500">{inviteForm.errors.password}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{t.access_role}</label>
                                            <select
                                                className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                                value={inviteForm.data.role}
                                                onChange={e => inviteForm.setData('role', e.target.value)}
                                            >
                                                <optgroup label={t.basic_roles}>
                                                    {Object.entries(basicRoles).map(([key, label]) => (
                                                        <option key={key} value={key}>{label}</option>
                                                    ))}
                                                </optgroup>
                                                <optgroup label={hasAdvancedRolesAddon ? t.advanced_roles : t.advanced_roles_locked}>
                                                    {Object.entries(advancedRoles).map(([key, label]) => (
                                                        <option key={key} value={key} disabled={!hasAdvancedRolesAddon}>
                                                            {label} {!hasAdvancedRolesAddon && "🔒"}
                                                        </option>
                                                    ))}
                                                </optgroup>
                                            </select>
                                            {inviteForm.errors.role && <p className="text-xs text-rose-500">{inviteForm.errors.role}</p>}

                                            {!hasAdvancedRolesAddon && (
                                                <p className="text-[11px] text-amber-600 mt-1 flex items-center gap-1">
                                                    <Lock className="w-3 h-3" /> {t.advanced_roles_unlock}
                                                </p>
                                            )}
                                        </div>

                                        <div className="pt-4 border-t flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="shadow-none"
                                                onClick={() => setShowInviteModal(false)}
                                            >
                                                {t.cancel}
                                            </Button>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                disabled={inviteForm.processing}
                                                className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                                            >
                                                {inviteForm.processing && <Loader2 className="h-3 w-3 animate-spin" />}
                                                {t.send_invite}
                                            </Button>
                                        </div>
                                    </form>
                                </OperationalCard>
                            </div>
                        )}

                        {/* EDIT TEAM MEMBER MODAL */}
                        {showEditModal && selectedMember && (
                            <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                                <OperationalCard className="w-full max-w-md shadow-2xl animate-scale-up">
                                    <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                        <h3 className="font-semibold text-slate-800 text-[14px]">{t.update_member_details}</h3>
                                        <button onClick={() => { setShowEditModal(false); setSelectedMember(null); }} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                            <X className="h-4 w-4" />
                                        </button>
                                    </div>
                                    <form onSubmit={handleEditSubmit} className="p-6 space-y-4">
                                        <div>
                                            <h4 className="font-semibold text-slate-800 text-[13px]">{selectedMember.name}</h4>
                                            <p className="text-xs text-slate-400 mt-0.5">{selectedMember.email}</p>
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{t.access_role}</label>
                                            <select
                                                className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                                value={editForm.data.role}
                                                onChange={e => editForm.setData('role', e.target.value)}
                                            >
                                                <optgroup label={t.basic_roles}>
                                                    {Object.entries(basicRoles).map(([key, label]) => (
                                                        <option key={key} value={key}>{label}</option>
                                                    ))}
                                                </optgroup>
                                                <optgroup label={hasAdvancedRolesAddon ? t.advanced_roles : t.advanced_roles_locked}>
                                                    {Object.entries(advancedRoles).map(([key, label]) => (
                                                        <option key={key} value={key} disabled={!hasAdvancedRolesAddon}>
                                                            {label} {!hasAdvancedRolesAddon && "🔒"}
                                                        </option>
                                                    ))}
                                                </optgroup>
                                            </select>
                                            {editForm.errors.role && <p className="text-xs text-rose-500">{editForm.errors.role}</p>}

                                            {!hasAdvancedRolesAddon && (
                                                <p className="text-[11px] text-amber-600 mt-1 flex items-center gap-1">
                                                    <Lock className="w-3 h-3" /> {t.advanced_roles_unlock}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{t.account_status}</label>
                                            <select
                                                className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                                value={editForm.data.status}
                                                onChange={e => editForm.setData('status', e.target.value as 'active' | 'suspended')}
                                            >
                                                <option value="active">{t.active_access_allowed}</option>
                                                <option value="suspended">{t.suspended_access_blocked}</option>
                                            </select>
                                            {editForm.errors.status && <p className="text-xs text-rose-500">{editForm.errors.status}</p>}

                                            {editForm.data.status === 'active' && selectedMember.status !== 'active' && activeMembersCount >= capacityLimit && (
                                                <div className="mt-2 p-2 bg-rose-50 border border-rose-100 rounded text-rose-600 text-[11px] flex items-start gap-1.5">
                                                    <AlertCircle className="w-3.5 h-3.5 shrink-0 mt-0.5" />
                                                    <span>{t.capacity_limit_warning}</span>
                                                </div>
                                            )}
                                        </div>

                                        <div className="pt-4 border-t flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="shadow-none"
                                                onClick={() => { setShowEditModal(false); setSelectedMember(null); }}
                                            >
                                                {t.cancel}
                                            </Button>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                disabled={editForm.processing || (editForm.data.status === 'active' && selectedMember.status !== 'active' && activeMembersCount >= capacityLimit)}
                                                className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                                            >
                                                {editForm.processing && <Loader2 className="h-3 w-3 animate-spin" />}
                                                {t.save_changes}
                                            </Button>
                                        </div>
                                    </form>
                                </OperationalCard>
                            </div>
                        )}

                        {/* CONFIRM DELETE MODAL */}
                        <ConfirmModal
                            isOpen={deleteConfirm.open}
                            title={t.remove_team_member}
                            description={`${t.remove_member}: ${deleteConfirm.member?.name || ''}`}
                            confirmLabel={t.remove_member}
                            variant="danger"
                            onConfirm={confirmDelete}
                            onCancel={() => setDeleteConfirm({ open: false, member: null })}
                        />
                    </>
                )}
            </div>
        </CrmLayout>
    );
}
