import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { ArrowLeft, UserPlus } from 'lucide-react';

export default function AffiliateModeratorsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: ''
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('affiliate_pos.affiliate.moderators.store'));
    };

    return (
        <div className="p-6 max-w-2xl mx-auto space-y-6 font-sans">
            <Head title="Invite Moderator" />

            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" onClick={() => window.history.back()} className="rounded-full bg-white shadow-sm border border-gray-200">
                    <ArrowLeft className="w-5 h-5 text-gray-600" />
                </Button>
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 tracking-tight">Invite Moderator</h1>
                    <p className="text-sm text-gray-500 mt-1">Create a sub-account for your cashier or team member.</p>
                </div>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <Card className="shadow-sm border-gray-200 bg-white">
                    <CardHeader className="bg-gray-50/50 border-b p-5">
                        <CardTitle className="text-lg flex items-center gap-2">
                            <UserPlus className="w-5 h-5 text-gray-500" /> Account Details
                        </CardTitle>
                        <CardDescription>They will log in with these credentials to manage orders on your behalf.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-6 space-y-5">
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-700">Full Name</label>
                            <Input 
                                value={data.name} 
                                onChange={e => setData('name', e.target.value)} 
                                placeholder="e.g. Ahmed Ali"
                                className="bg-gray-50/50 focus:bg-white"
                            />
                            {errors.name && <div className="text-sm text-red-600">{errors.name}</div>}
                        </div>
                        
                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-700">Email Address</label>
                            <Input 
                                type="email" 
                                value={data.email} 
                                onChange={e => setData('email', e.target.value)} 
                                placeholder="ahmed@example.com"
                                className="bg-gray-50/50 focus:bg-white"
                            />
                            {errors.email && <div className="text-sm text-red-600">{errors.email}</div>}
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium text-gray-700">Password</label>
                            <Input 
                                type="password" 
                                value={data.password} 
                                onChange={e => setData('password', e.target.value)} 
                                placeholder="Min. 8 characters"
                                className="bg-gray-50/50 focus:bg-white"
                            />
                            {errors.password && <div className="text-sm text-red-600">{errors.password}</div>}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-3">
                    <Button type="button" variant="outline" onClick={() => window.history.back()} disabled={processing}>
                        Cancel
                    </Button>
                    <Button type="submit" className="bg-blue-600 hover:bg-blue-700 shadow-md" disabled={processing}>
                        <UserPlus className="w-4 h-4 mr-2" />
                        Create Account
                    </Button>
                </div>
            </form>
        </div>
    );
}
