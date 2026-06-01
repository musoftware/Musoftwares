import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Mail, Phone, ExternalLink, Clock, Tag, ArrowLeft, MoreHorizontal, Edit, Trash2, UserPlus, KeyRound } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ConfirmModal } from '@/Components/ui/ConfirmModal';

export default function LegacyCoWorkerShow({ worker }) {
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [isCreateUserDialogOpen, setIsCreateUserDialogOpen] = useState(false);
    const [isResetPasswordDialogOpen, setIsResetPasswordDialogOpen] = useState(false);

    const handleDelete = () => {
        router.delete(`/admin/users/legacy-coworker/${worker.id}`);
    };

    const handleCreateUser = () => {
        router.post(`/admin/users/legacy-coworker/${worker.id}/create-user`);
        setIsCreateUserDialogOpen(false);
    };

    const handleResetPassword = () => {
        router.post(`/admin/users/legacy-coworker/${worker.id}/reset-password`);
        setIsResetPasswordDialogOpen(false);
    };

    return (
        <AdminSidebarLayout title={__('general.legacy_co_worker_details')} header="Co-Worker Details">
            <Head title={`Co-Worker - ${worker.person_name}`} />

            <div className="mb-6">
                <Link
                    href="/admin/users/co-work"
                    className="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors"
                >
                    <ArrowLeft className="w-4 h-4 mr-1" />{__('general.back_to_co_work')}</Link>
            </div>

            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden max-w-4xl">
                {/* Header Section */}
                <div className="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-6 items-start sm:items-center justify-between">
                    <div className="flex items-center gap-5">
                        <div className="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-3xl shadow-inner flex-shrink-0">
                            {worker.person_name?.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '??'}
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 mb-1">{worker.person_name || '—'}</h1>
                            <p className="text-slate-500 text-sm flex items-center gap-2">
                                <span className="inline-flex items-center gap-1">
                                    <Clock className="w-4 h-4 text-slate-400" /> Joined {new Date(worker.created_at).toLocaleDateString()}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    {/* Action Menu */}
                    <div className="flex items-center gap-2 mt-4 sm:mt-0">
                        <Link href={`/admin/users/legacy-coworker/${worker.id}/edit`}>
                            <Button variant="outline" size="sm" className="hidden sm:flex items-center gap-2">
                                <Edit className="w-4 h-4" />{__('general.edit_co_worker')}</Button>
                        </Link>
                        
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="w-9 px-0">
                                    <MoreHorizontal className="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href={`/admin/users/legacy-coworker/${worker.id}/edit`} className="cursor-pointer flex items-center">
                                        <Edit className="w-4 h-4 mr-2 text-slate-500" />{__('general.edit_co_worker')}</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => setIsCreateUserDialogOpen(true)} className="cursor-pointer">
                                    <UserPlus className="w-4 h-4 mr-2 text-slate-500" />{__('general.create_user_account')}</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => setIsResetPasswordDialogOpen(true)} className="cursor-pointer">
                                    <KeyRound className="w-4 h-4 mr-2 text-slate-500" />{__('general.reset_password')}</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => setIsDeleteDialogOpen(true)} className="cursor-pointer text-red-600 focus:text-red-700 focus:bg-red-50">
                                    <Trash2 className="w-4 h-4 mr-2" />{__('general.delete_co_worker')}</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                {/* Details Section */}
                <div className="p-6 sm:p-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {/* Contact Info */}
                        <div className="space-y-6">
                            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">{__('general.contact_information')}</h2>
                            
                            <div className="space-y-4">
                                {worker.email && (
                                    <div className="flex items-start gap-3">
                                        <div className="p-2 bg-slate-100 rounded-lg text-slate-500 flex-shrink-0">
                                            <Mail className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium text-slate-500 mb-0.5">{__('general.email_address')}</p>
                                            <p className="text-slate-900 font-medium break-all">{worker.email}</p>
                                        </div>
                                    </div>
                                )}

                                {worker.mobile && (
                                    <div className="flex items-start gap-3">
                                        <div className="p-2 bg-slate-100 rounded-lg text-slate-500 flex-shrink-0">
                                            <Phone className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium text-slate-500 mb-0.5">{__('general.mobile_number')}</p>
                                            <p className="text-slate-900 font-medium flex items-center gap-2">
                                                {worker.flag_path && (
                                                    <img src={worker.flag_path} alt="flag" className="w-5 h-3.5 object-cover rounded-sm shadow-sm" />
                                                )}
                                                {worker.mobile}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {worker.whatsapp && (
                                    <div className="flex items-start gap-3">
                                        <div className="p-2 bg-green-50 text-green-600 rounded-lg flex-shrink-0">
                                            <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium text-slate-500 mb-0.5">WhatsApp</p>
                                            <a
                                                href={`https://wa.me/${worker.whatsapp.replace(/\D/g, '')}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-green-600 font-medium hover:underline inline-flex items-center gap-1"
                                            >{__('general.message_on_whatsapp')}<ExternalLink className="w-3.5 h-3.5" />
                                            </a>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Social & Work Info */}
                        <div className="space-y-6">
                            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">{__('general.social_availability')}</h2>

                            <div className="space-y-4">
                                {(worker.facebook || worker.linked_in) && (
                                    <div className="flex items-start gap-3">
                                        <div className="p-2 bg-blue-50 text-blue-600 rounded-lg flex-shrink-0">
                                            <ExternalLink className="w-5 h-5" />
                                        </div>
                                        <div className="space-y-2">
                                            <p className="text-xs font-medium text-slate-500 mb-0.5">{__('general.social_profiles')}</p>
                                            {worker.facebook && (
                                                <div>
                                                    <a href={worker.facebook} target="_blank" rel="noopener noreferrer" className="text-blue-600 font-medium hover:underline inline-flex items-center gap-1">{__('general.facebook_profile')}<ExternalLink className="w-3.5 h-3.5" />
                                                    </a>
                                                </div>
                                            )}
                                            {worker.linked_in && (
                                                <div>
                                                    <a href={worker.linked_in} target="_blank" rel="noopener noreferrer" className="text-sky-700 font-medium hover:underline inline-flex items-center gap-1">{__('general.linkedin_profile')}<ExternalLink className="w-3.5 h-3.5" />
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {(worker.time_from || worker.time_to) && (
                                    <div className="flex items-start gap-3">
                                        <div className="p-2 bg-amber-50 text-amber-600 rounded-lg flex-shrink-0">
                                            <Clock className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium text-slate-500 mb-0.5">{__('general.available_hours')}</p>
                                            <p className="text-slate-900 font-medium">
                                                {worker.time_from || '?'} to {worker.time_to || '?'}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Tech Tags */}
                    {worker.tech_tags?.length > 0 && (
                        <div className="mt-8 pt-8 border-t border-slate-100">
                            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">{__('general.technical_skills_tags')}</h2>
                            <div className="flex flex-wrap gap-2">
                                {worker.tech_tags.map(t => (
                                    <span key={t.id} className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <Tag className="w-4 h-4" /> {t.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modals */}
            <ConfirmModal
                isOpen={isDeleteDialogOpen}
                onCancel={() => setIsDeleteDialogOpen(false)}
                onConfirm={handleDelete}
                title={__('general.delete_legacy_co_worker')}
                description={`Are you sure you want to delete ${worker.person_name}? This action cannot be undone.`}
                confirmLabel="Delete"
                variant="danger"
            />
            
            <ConfirmModal
                isOpen={isCreateUserDialogOpen}
                onCancel={() => setIsCreateUserDialogOpen(false)}
                onConfirm={handleCreateUser}
                title={__('general.create_user_account')}
                description={`Are you sure you want to create an Employee user account for ${worker.person_name}? Credentials will be generated and sent via WhatsApp if available.`}
                confirmLabel="Create User"
            />

            <ConfirmModal
                isOpen={isResetPasswordDialogOpen}
                onCancel={() => setIsResetPasswordDialogOpen(false)}
                onConfirm={handleResetPassword}
                title={__('general.reset_password_send_credentials')}
                description={`Are you sure you want to reset the password for ${worker.person_name}'s account? New credentials will be generated and sent via WhatsApp if available.`}
                confirmLabel="Reset Password"
            />
        </AdminSidebarLayout>
    );
}
