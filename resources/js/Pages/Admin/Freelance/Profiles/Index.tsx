import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { MoreHorizontal, Edit, Trash2, User } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function Index({ profiles, filters }: any) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: any) => {
        e.preventDefault();
        router.get(route('admin.freelance.profiles.index'), { search }, { preserveState: true });
    };

    const handleDelete = (id: any) => {
        if (confirm('Are you sure you want to delete this profile?')) {
            router.delete(route('admin.freelance.profiles.destroy', id));
        }
    };

    return (
        <AdminSidebarLayout title={__('admin.freelance_profiles', undefined, 'Freelance Profiles')} header={__('admin.manage_freelance_profiles', undefined, 'Manage Freelance Profiles')}>
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex space-x-2">
                    <Input 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder={__('admin.search_profiles', undefined, 'Search profiles...')}
                        className="w-64"
                    />
                    <Button type="submit" variant="secondary">{__('freelance.search')}</Button>
                    {search && (
                        <Button type="button" variant="ghost" onClick={() => { setSearch(''); router.get(route('admin.freelance.profiles.index')); }}>
                            {__('freelance.clear')}
                        </Button>
                    )}
                </form>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-gray-50">
                        <tr>
                            <th className="p-4 font-medium text-gray-600">ID</th>
                            <th className="p-4 font-medium text-gray-600">{__('admin.user_name', undefined, 'User')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('admin.title', undefined, 'Title')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('admin.bio', undefined, 'Bio')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('admin.skills', undefined, 'Skills')}</th>
                            <th className="p-4 font-medium text-gray-600">{__('admin.notifications_enabled', undefined, 'Notifications')}</th>
                            <th className="p-4 font-medium text-gray-600 text-right">{__('freelance.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(profiles.data as any).map((profile: any) => (
                            <tr key={profile.id} className="border-b hover:bg-gray-50">
                                <td className="p-4 font-medium text-gray-900">{profile.id}</td>
                                <td className="p-4 font-medium text-gray-900">{profile.user?.name || '-'}</td>
                                <td className="p-4 text-gray-500">{profile.title || '-'}</td>
                                <td className="p-4 text-gray-500">
                                    <div className="max-w-xs truncate" title={profile.bio}>
                                        {profile.bio || '-'}
                                    </div>
                                </td>
                                <td className="p-4 text-gray-500">
                                    <div className="flex flex-wrap gap-1">
                                        {profile.user?.skills?.length > 0 ? (
                                            profile.user.skills.map((us: any) => (
                                                <span key={us.id} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                                    {us.skill?.name || 'Unknown'}
                                                </span>
                                            ))
                                        ) : (
                                            <span>-</span>
                                        )}
                                    </div>
                                </td>
                                <td className="p-4">
                                    {profile.user?.fcm_token || profile.user?.last_shortcut_sync_at ? (
                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            {__('general.yes', undefined, 'Yes')}
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            {__('general.no', undefined, 'No')}
                                        </span>
                                    )}
                                </td>
                                <td className="p-4 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                <span className="sr-only">{__('general.open_menu')}</span>
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-56">
                                            <DropdownMenuLabel>{__('freelance.actions')}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem asChild className="cursor-pointer">
                                                <Link href={`/admin/users/${profile.user_id}`}>
                                                    <User className="mr-2 h-4 w-4 text-purple-600" />
                                                    <span>{__('admin.view_user_profile', undefined, 'View User Profile')}</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild className="cursor-pointer">
                                                <Link href={route('admin.freelance.profiles.edit', profile.id)}>
                                                    <Edit className="mr-2 h-4 w-4 text-blue-600" />
                                                    <span>{__('freelance.edit')}</span>
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => handleDelete(profile.id)} className="text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer">
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                <span>{__('freelance.delete')}</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        ))}
                        {(profiles.data as any).length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-4 text-center text-gray-500">
                                    {__('admin.no_profiles_found', undefined, 'No profiles found.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {profiles.links && profiles.links.length > 3 && (
                <div className="mt-4 flex justify-between items-center">
                    <div className="text-sm text-gray-500">
                        Showing {profiles.from || 0} to {profiles.to || 0} of {profiles.total} results
                    </div>
                    <div className="flex space-x-1">
                        {profiles.links.map((link: any, idx: number) => (
                            <Link 
                                key={idx}
                                href={link.url || '#'}
                                className={`px-3 py-1 rounded text-sm transition ${link.active ? 'bg-slate-900 text-white shadow-sm' : !link.url ? 'cursor-not-allowed opacity-50 text-slate-300 pointer-events-none' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </AdminSidebarLayout>
    );
}
