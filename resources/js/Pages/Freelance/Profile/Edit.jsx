import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import FreelanceLayout from '@/Pages/Freelance/Layout';
import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { X, Plus, User } from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import { 
    Command, 
    CommandEmpty, 
    CommandGroup, 
    CommandInput, 
    CommandItem, 
    CommandList 
} from "@/Components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";

export default function EditProfile({ profile, userSkills, availableSkills, reviews }) {
    const { toast } = useToast();
    const [openSkillSearch, setOpenSkillSearch] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        title: profile?.title || '',
        bio: profile?.bio || '',
        hourly_rate: profile?.hourly_rate ? Number(profile.hourly_rate) : '',
    });

    const submitProfile = (e) => {
        e.preventDefault();
        put('/freelance/profile', {
            preserveScroll: true,
            onSuccess: () => {
                toast({
                    title: __('general.saved_successfully'),
                    description: __('freelance.profile_updated'),
                });
            }
        });
    };

    const addSkill = (skillId) => {
        router.post('/freelance/user-skills', { skill_id: skillId }, {
            preserveScroll: true,
            onSuccess: () => setOpenSkillSearch(false)
        });
    };

    const removeSkill = (skillId) => {
        router.delete(`/freelance/user-skills/${skillId}`, {
            preserveScroll: true,
        });
    };

    // Filter available skills to exclude ones already added
    const unselectedSkills = availableSkills.filter(
        as => !userSkills.some(us => us.id === as.id)
    );

    return (
        <FreelanceLayout>
            <Head title={__('general.my_profile')} />

            <div className="max-w-7xl w-full mx-auto space-y-6">
                <div className="flex items-center gap-3 mb-6">
                    <User className="w-8 h-8 text-emerald-600" />
                    <h1 className="text-2xl font-bold text-slate-900">{__('general.my_profile')}</h1>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{__('freelance.profile_details')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitProfile} className="space-y-4">
                            <div>
                                <Label htmlFor="title">{__('freelance.professional_title')}</Label>
                                <Input 
                                    id="title"
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                    placeholder={__('freelance.e_g_senior_laravel_developer')}
                                />
                                {errors.title && <p className="text-sm text-red-500 mt-1">{errors.title}</p>}
                            </div>

                            <div>
                                <Label htmlFor="bio">{__('freelance.bio')}</Label>
                                <Textarea 
                                    id="bio"
                                    value={data.bio}
                                    onChange={e => setData('bio', e.target.value)}
                                    rows={4}
                                    placeholder={__('freelance.describe_your_experience_and_skills')}
                                />
                                {errors.bio && <p className="text-sm text-red-500 mt-1">{errors.bio}</p>}
                            </div>

                            <div>
                                <Label htmlFor="hourly_rate">{__('freelance.hourly_rate')}</Label>
                                <div className="relative">
                                    <span className="absolute start-3 top-1/2 -translate-y-1/2 text-slate-500">$</span>
                                    <Input 
                                        id="hourly_rate"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        className="ps-7"
                                        value={data.hourly_rate}
                                        onChange={e => setData('hourly_rate', e.target.value)}
                                    />
                                </div>
                                {errors.hourly_rate && <p className="text-sm text-red-500 mt-1">{errors.hourly_rate}</p>}
                            </div>

                            <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                {__('general.save')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{__('freelance.my_skills')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2 mb-6">
                            {userSkills.map((skill) => (
                                <Badge key={skill.id} variant="secondary" className="px-3 py-1 text-sm bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200">
                                    {skill.name}
                                    <button 
                                        onClick={() => removeSkill(skill.pivot.id)}
                                        className="ms-2 hover:text-red-500 focus:outline-none"
                                        title={__('general.remove')}
                                    >
                                        <X className="w-3 h-3" />
                                    </button>
                                </Badge>
                            ))}
                            {userSkills.length === 0 && (
                                <p className="text-sm text-slate-500">{__('freelance.no_skills_added_yet')}</p>
                            )}
                        </div>

                        <Popover open={openSkillSearch} onOpenChange={setOpenSkillSearch}>
                            <PopoverTrigger asChild>
                                <Button variant="outline" role="combobox" aria-expanded={openSkillSearch} className="w-full sm:w-[300px] justify-between">
                                    <div className="flex items-center text-slate-500">
                                        <Plus className="w-4 h-4 me-2" />
                                        {__('freelance.add_skill')}
                                    </div>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-[300px] p-0" align="start">
                                <Command>
                                    <CommandInput placeholder={__('freelance.search_skills')} />
                                    <CommandList>
                                        <CommandEmpty>{__('general.no_results_found')}</CommandEmpty>
                                        <CommandGroup>
                                            {unselectedSkills.map((skill) => (
                                                <CommandItem
                                                    key={skill.id}
                                                    value={skill.name}
                                                    onSelect={() => addSkill(skill.id)}
                                                >
                                                    {skill.name}
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex justify-between items-center">
                            <span>{__('freelance.reviews_and_ratings')}</span>
                            <div className="flex items-center space-x-2 text-sm font-normal">
                                <span className="text-yellow-500">★</span>
                                <span className="font-bold">{profile.average_rating ? Number(profile.average_rating).toFixed(1) : '0.0'}</span>
                                <span className="text-slate-500">({profile.reviews_count} {__('freelance.reviews')})</span>
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {reviews && reviews.length > 0 ? (
                            <div className="space-y-4">
                                {reviews.map((review) => (
                                    <div key={review.id} className="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                                        <div className="flex justify-between items-start mb-2">
                                            <div className="font-semibold text-slate-900">{review.reviewer.name}</div>
                                            <div className="flex text-yellow-500 text-sm">
                                                {Array.from({ length: 5 }).map((_, i) => (
                                                    <span key={i} className={i < review.rating ? "opacity-100" : "opacity-30"}>★</span>
                                                ))}
                                            </div>
                                        </div>
                                        <p className="text-sm text-slate-600">{review.comment}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-slate-500 text-center py-4">{__('freelance.no_visible_reviews_yet')}</p>
                        )}
                    </CardContent>
                </Card>

            </div>
        </FreelanceLayout>
    );
}
