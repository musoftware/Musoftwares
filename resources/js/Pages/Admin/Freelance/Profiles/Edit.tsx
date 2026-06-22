import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { Checkbox } from '@/Components/ui/checkbox';

export default function Edit({ profile, availableSkills }: any) {
    // profile.user.skills is an array of objects like { id, skill_id, user_id, skill: { id, name } }
    const currentSkillIds = profile.user?.skills?.map((s: any) => s.skill_id) || [];

    const { data, setData, put, processing, errors } = useForm({
        title: profile.title || '',
        bio: profile.bio || '',
        skills: currentSkillIds,
    });

    const handleSubmit = (e: any) => {
        e.preventDefault();
        put(route('admin.freelance.profiles.update', profile.id));
    };

    const toggleSkill = (skillId: number) => {
        const current = new Set(data.skills);
        if (current.has(skillId)) {
            current.delete(skillId);
        } else {
            current.add(skillId);
        }
        setData('skills', Array.from(current));
    };

    return (
        <AdminSidebarLayout 
            title={__('admin.edit_profile', undefined, 'Edit Profile')} 
            header={__('admin.edit_freelance_profile', undefined, 'Edit Freelance Profile')}
        >
            <div className="mb-6 flex items-center justify-between">
                <Link href={route('admin.freelance.profiles.index')}>
                    <Button variant="outline">{__('general.back', undefined, 'Back')}</Button>
                </Link>
            </div>

            <div className="w-full max-w-7xl mx-auto">
                <div className="bg-white rounded-lg shadow border border-slate-200">
                    <div className="p-6 border-b border-slate-200">
                        <h2 className="text-lg font-medium text-slate-900">
                            {__('admin.profile_for', { name: profile.user?.name }, `Profile for ${profile.user?.name}`)}
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            {__('admin.update_freelancer_details', undefined, 'Update the freelancer\'s title, bio, and skills.')}
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="title">{__('admin.title', undefined, 'Title')}</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder={__('admin.title_placeholder', undefined, 'e.g. Senior Full Stack Developer')}
                                />
                                {errors.title && <p className="text-sm text-red-600 mt-1">{errors.title}</p>}
                            </div>

                            <div>
                                <Label htmlFor="bio">{__('admin.bio', undefined, 'Bio')}</Label>
                                <textarea
                                    id="bio"
                                    className="flex min-h-[150px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 mt-1"
                                    value={data.bio}
                                    onChange={(e) => setData('bio', e.target.value)}
                                    placeholder={__('admin.bio_placeholder', undefined, 'Describe the freelancer\'s experience and expertise...')}
                                />
                                {errors.bio && <p className="text-sm text-red-600 mt-1">{errors.bio}</p>}
                            </div>

                            <div>
                                <Label>{__('admin.skills', undefined, 'Skills')}</Label>
                                <div className="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 border rounded-md p-4 bg-slate-50 h-[300px] overflow-y-auto">
                                    {availableSkills.map((skill: any) => (
                                        <div key={skill.id} className="flex items-center space-x-2">
                                            <Checkbox
                                                id={`skill-${skill.id}`}
                                                checked={data.skills.includes(skill.id)}
                                                onCheckedChange={() => toggleSkill(skill.id)}
                                            />
                                            <Label 
                                                htmlFor={`skill-${skill.id}`} 
                                                className="text-sm font-normal cursor-pointer leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                            >
                                                {skill.name}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                                {errors.skills && <p className="text-sm text-red-600 mt-1">{errors.skills}</p>}
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-6 border-t border-slate-200">
                            <Link href={route('admin.freelance.profiles.index')}>
                                <Button type="button" variant="outline">
                                    {__('general.cancel', undefined, 'Cancel')}
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {__('general.save', undefined, 'Save Changes')}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminSidebarLayout>
    );
}
