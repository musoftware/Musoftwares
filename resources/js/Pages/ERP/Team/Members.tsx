import React, { useState } from 'react';
import ERPLayout from '@/Layouts/ERPLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';
import { 
    LayoutDashboard, 
    Users, 
    Briefcase, 
    CheckSquare, 
    FileText, 
    History, 
    Folder, 
    Pin, 
    Calendar as CalendarIcon, 
    UserCheck, 
    Settings, 
    Plus, 
    Trash2, 
    Edit2, 
    X, 
    Mail, 
    Key, 
    Shield,
    Loader2,
    Lock,
    Unlock,
    UserPlus,
    ArrowRight,
    AlertCircle
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { OperationalCard } from '@/Components/ui/OperationalCard';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';
import { useERPMenu } from '@/hooks/useERPMenu';
import { __ } from '@/lib/i18n';

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

interface MembersProps {
    members: TeamMember[];
    hasFeature: boolean;
    capacityLimit: number;
    activeMembersCount: number;
    hasAdvancedRolesAddon: boolean;
    basicRoles: Record<string, string>;
    advancedRoles: Record<string, string>;
    auth: {
        user: {
            name: string;
            email: string;
        };
        team_member: any;
    };
}

export default function Members({ members, hasFeature, capacityLimit, activeMembersCount, hasAdvancedRolesAddon, basicRoles, advancedRoles, auth }: MembersProps) {
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
        inviteForm.post(route('erp.team-members.store'), {
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

        editForm.put(route('erp.team-members.update', selectedMember.id), {
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

        const memberId = deleteConfirm.member.id;
        inviteForm.delete(route('erp.team-members.destroy', memberId), {
            onSuccess: () => {
                setDeleteConfirm({ open: false, member: null });
            },
        });
    };

    // Use shared ERP menu hook
    const { menuItems: mappedMenuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('team');

    return (
        <ERPLayout 
            title={__('general.team_members')}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={mappedMenuItems}
            lockedAddons={lockedAddons}
        >
            <div className="space-y-6">
                {!hasFeature ? (
                    <UpgradeOverlay 
                        title={__('general.collaborate_with_your_team_in_real_time')}
                        description={__('general.unlock_team_members_to_invite_managers_and_staff_to_your_workspace_assign_roles_control_access_to_finances_and_tasks_and_track_activity_all_from_one_place')}
                        icon={Users}
                        module="erp-team-members"
                        priceText="Unlock Team Members for 500 EGP/Yr"
                        features={[
                            { icon: Shield, text: "Role-based access" },
                            { icon: UserPlus, text: "Invite by email" },
                            { icon: Lock, text: "Suspend anytime" }
                        ]}
                    />
                ) : (
                    <>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-semibold text-slate-900 tracking-tight">{__('general.team_members')}</h2>
                        <p className="text-sm text-slate-500 mt-1">{__('general.invite_team_members_and_assign_specific_crm_roles_and_access_levels')}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="text-[13px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1.5 rounded-full shadow-sm flex items-center gap-2">
                            <Users className="w-4 h-4 text-slate-400" />
                            <span>
                                <span className={activeMembersCount >= capacityLimit ? "text-rose-600" : "text-indigo-600"}>
                                    {activeMembersCount}
                                </span>
                                {' '} / {capacityLimit} Seats Used
                            </span>
                        </div>
                        {activeMembersCount >= capacityLimit ? (
                            <Button size="sm" variant="outline" className="shadow-sm border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 flex items-center gap-1.5 cursor-not-allowed">
                                <Lock className="h-3 w-3" />{__('general.upgrade_capacity')}</Button>
                        ) : (
                            <Button 
                                size="sm" 
                                onClick={() => setShowInviteModal(true)} 
                                className="shadow-sm bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                            >
                                <UserPlus className="h-4 w-4" />{__('general.invite_member')}</Button>
                        )}
                    </div>
                </div>

                <div className="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-sm text-indigo-700 flex items-center gap-2">
                    <Key className="w-4 h-4" />
                    <span>Your team members can log in to their specific workspace at: <a href="/crm/portal/login" target="_blank" className="font-bold hover:underline">{window.location.origin}/crm/portal/login</a></span>
                </div>

                {/* Team Members List */}
                <OperationalCard>
                    <div className="overflow-x-auto">
                        <table className="w-full text-start text-sm border-collapse">
                            <thead>
                                <tr className="bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th className="px-6 py-3.5">{__('general.name')}</th>
                                    <th className="px-6 py-3.5">{__('general.email')}</th>
                                    <th className="px-6 py-3.5">{__('general.role')}</th>
                                    <th className="px-6 py-3.5">{__('general.status')}</th>
                                    <th className="px-6 py-3.5">{__('general.joined')}</th>
                                    <th className="px-6 py-3.5">{__('general.last_login')}</th>
                                    <th className="px-6 py-3.5 text-end">{__('general.actions')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {members.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="p-0">
                                            <div className="flex flex-col items-center justify-center p-12 text-center">
                                                <Users className="h-12 w-12 text-slate-300 mb-4" />
                                                <h3 className="font-semibold text-slate-800 text-sm">{__('general.no_team_members_yet')}</h3>
                                                <p className="text-xs text-slate-500 max-w-xs mt-1">{__('general.invite_managers_or_members_to_grant_secure_role_based_access')}</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    members.map((member) => (
                                        <tr key={member.id} className="hover:bg-slate-50 transition text-[13px] text-slate-700">
                                            <td className="px-6 py-4 font-semibold text-slate-900">
                                                {member.name}
                                            </td>
                                            <td className="px-6 py-4 font-mono text-xs text-slate-600">
                                                {member.email}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                                    <Briefcase className="h-3 w-3 text-slate-500" />
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
                                                    {member.status === 'active' ? 'Active' : 'Suspended'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                                                {member.invited_at}
                                            </td>
                                            <td className="px-6 py-4 text-slate-500 font-mono text-xs">
                                                {member.last_login_at}
                                            </td>
                                            <td className="px-6 py-4 text-end">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button 
                                                        onClick={() => handleEditClick(member)}
                                                        className="p-1.5 hover:bg-slate-100 rounded text-slate-500 transition-colors"
                                                        title={__('general.edit_role_or_status')}
                                                    >
                                                        <Edit2 className="h-4 w-4" />
                                                    </button>
                                                    <button 
                                                        onClick={() => handleDeleteClick(member)}
                                                        className="p-1.5 hover:bg-rose-50 rounded text-rose-500 transition-colors"
                                                        title={__('general.delete_member')}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </OperationalCard>

            {/* INVITE TEAM MEMBER MODAL */}
            {showInviteModal && (
                <div className="fixed inset-0 bg-slate-950/20 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in">
                    <OperationalCard className="w-full max-w-md shadow-2xl animate-scale-up">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-semibold text-slate-800 text-[14px]">{__('general.invite_team_member')}</h3>
                            <button onClick={() => setShowInviteModal(false)} className="p-1 hover:bg-slate-100 rounded text-slate-400">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={handleInviteSubmit} className="p-6 space-y-4">
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{__('general.full_name')}</label>
                                <Input 
                                    required 
                                    placeholder={__('general.john_doe')} 
                                    value={inviteForm.data.name} 
                                    onChange={e => inviteForm.setData('name', e.target.value)} 
                                    className="shadow-none" 
                                />
                                {inviteForm.errors.name && <p className="text-xs text-rose-500">{inviteForm.errors.name}</p>}
                            </div>
                            
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{__('general.email_address')}</label>
                                <Input 
                                    required 
                                    type="email" 
                                    placeholder={__('general.john_example_com')} 
                                    value={inviteForm.data.email} 
                                    onChange={e => inviteForm.setData('email', e.target.value)} 
                                    className="shadow-none" 
                                />
                                {inviteForm.errors.email && <p className="text-xs text-rose-500">{inviteForm.errors.email}</p>}
                            </div>

                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{__('general.temporary_password')}</label>
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
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Access Role (Job Title)</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={inviteForm.data.role}
                                    onChange={e => inviteForm.setData('role', e.target.value)}
                                >
                                    <optgroup label={__('general.basic_roles')}>
                                        {Object.entries(basicRoles).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </optgroup>
                                    <optgroup label={hasAdvancedRolesAddon ? "Advanced Roles" : "Advanced Roles (Requires Addon)"}>
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
                                        <Lock className="w-3 h-3" />{__('general.get_the')}<strong>{__('general.advanced_roles')}</strong>{__('general.addon_to_unlock_manager_titles')}</p>
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
                                    {__('general.cancel')}</Button>
                                <Button 
                                    type="submit" 
                                    size="sm" 
                                    disabled={inviteForm.processing}
                                    className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                                >
                                    {inviteForm.processing && <Loader2 className="h-3 w-3 animate-spin" />}
                                    Send Invite
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
                            <h3 className="font-semibold text-slate-800 text-[14px]">{__('general.update_team_member_details')}</h3>
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
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Access Role (Job Title)</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={editForm.data.role}
                                    onChange={e => editForm.setData('role', e.target.value)}
                                >
                                    <optgroup label={__('general.basic_roles')}>
                                        {Object.entries(basicRoles).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </optgroup>
                                    <optgroup label={hasAdvancedRolesAddon ? "Advanced Roles" : "Advanced Roles (Requires Addon)"}>
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
                                        <Lock className="w-3 h-3" />{__('general.get_the')}<strong>{__('general.advanced_roles')}</strong>{__('general.addon_to_unlock_manager_titles')}</p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{__('general.account_status')}</label>
                                <select 
                                    className="flex h-10 w-full rounded-md border border-slate-200 bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-950 shadow-none"
                                    value={editForm.data.status}
                                    onChange={e => editForm.setData('status', e.target.value as 'active' | 'suspended')}
                                >
                                    <option value="active">Active (Access allowed)</option>
                                    <option value="suspended">Suspended (Access blocked)</option>
                                </select>
                                {editForm.errors.status && <p className="text-xs text-rose-500">{editForm.errors.status}</p>}
                                
                                {editForm.data.status === 'active' && selectedMember.status !== 'active' && activeMembersCount >= capacityLimit && (
                                    <div className="mt-2 p-2 bg-rose-50 border border-rose-100 rounded text-rose-600 text-[11px] flex items-start gap-1.5">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0 mt-0.5" />
                                        <span>You cannot activate this user because you have reached your {capacityLimit}-user capacity limit. Upgrade your capacity to activate.</span>
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
                                    {__('general.cancel')}</Button>
                                <Button 
                                    type="submit" 
                                    size="sm" 
                                    disabled={editForm.processing || (editForm.data.status === 'active' && selectedMember.status !== 'active' && activeMembersCount >= capacityLimit)}
                                    className="shadow-none bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                                >
                                    {editForm.processing && <Loader2 className="h-3 w-3 animate-spin" />}
                                    Save Changes
                                </Button>
                            </div>
                        </form>
                    </OperationalCard>
                </div>
            )}

            {/* CONFIRM DELETE MODAL */}
            <ConfirmModal 
                isOpen={deleteConfirm.open}
                title={__('general.remove_team_member')}
                description={`Are you sure you want to remove ${deleteConfirm.member?.name}? This will permanently revoke their access credentials. This cannot be undone.`}
                confirmLabel="Remove Member"
                variant="danger"
                onConfirm={confirmDelete}
                onCancel={() => setDeleteConfirm({ open: false, member: null })}
            />
            </>
                )}
            </div>
        </ERPLayout>
    );
}
