import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import FreelanceLayout from '@/Pages/Freelance/Layout';
import { __ } from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Label } from '@/Components/ui/label';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/Components/ui/card';
import { useToast } from '@/Components/ui/use-toast';
import { Settings, Bell, BellOff, Smartphone } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

export default function NotificationSettings({ profile }) {
    const { toast } = useToast();
    const { flash } = usePage().props;
    const [generatingToken, setGeneratingToken] = useState(false);

    // Determine current mute status
    const isMuted = profile?.notifications_muted_until && new Date(profile.notifications_muted_until) > new Date();

    const { data, setData, put, processing } = useForm({
        receive_job_notifications: profile?.receive_job_notifications ?? true,
        mute_duration: isMuted ? 'forever' : '', // We just default to empty if not currently muting in the UI flow
    });

    const submitSettings = (e) => {
        e.preventDefault();
        put('/freelance/settings/notifications', {
            preserveScroll: true,
            onSuccess: () => {
                toast({
                    title: __('general.saved_successfully'),
                    description: __('freelance.notification_preferences_updated'),
                });
            }
        });
    };

    return (
        <FreelanceLayout>
            <Head title={__('general.settings')} />

            <div className="max-w-3xl mx-auto space-y-6">
                <div className="flex items-center gap-3 mb-6">
                    <Settings className="w-8 h-8 text-emerald-600" />
                    <h1 className="text-2xl font-bold text-slate-900">{__('general.settings')}</h1>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            {data.receive_job_notifications ? (
                                <Bell className="w-5 h-5 text-emerald-600" />
                            ) : (
                                <BellOff className="w-5 h-5 text-slate-400" />
                            )}
                            <CardTitle>{__('freelance.notification_preferences')}</CardTitle>
                        </div>
                        <CardDescription>
                            {__('freelance.manage_how_you_receive_notifications_about_new_jobs')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitSettings} className="space-y-6">
                            
                            <div className="flex items-center justify-between p-4 border border-slate-100 rounded-lg bg-slate-50">
                                <div className="space-y-0.5">
                                    <Label className="text-base font-medium text-slate-900">
                                        {__('freelance.receive_job_notifications')}
                                    </Label>
                                    <p className="text-sm text-slate-500">
                                        {__('freelance.turn_off_to_completely_stop_receiving_job_matches')}
                                    </p>
                                </div>
                                <Switch
                                    checked={data.receive_job_notifications}
                                    onCheckedChange={(checked) => setData('receive_job_notifications', checked)}
                                />
                            </div>

                            {data.receive_job_notifications && (
                                <div className="p-4 border border-slate-100 rounded-lg">
                                    <Label className="text-base font-medium text-slate-900 block mb-2">
                                        {__('freelance.snooze_notifications')}
                                    </Label>
                                    <p className="text-sm text-slate-500 mb-4">
                                        {__('freelance.temporarily_mute_notifications_without_turning_them_off')}
                                    </p>
                                    
                                    <Select 
                                        value={data.mute_duration} 
                                        onValueChange={(val) => setData('mute_duration', val)}
                                    >
                                        <SelectTrigger className="w-full sm:w-[300px]">
                                            <SelectValue placeholder={__('freelance.select_snooze_duration')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">{__('freelance.dont_snooze')}</SelectItem>
                                            <SelectItem value="1_hour">{__('freelance.mute_for_1_hour')}</SelectItem>
                                            <SelectItem value="24_hours">{__('freelance.mute_for_24_hours')}</SelectItem>
                                            <SelectItem value="1_week">{__('freelance.mute_for_1_week')}</SelectItem>
                                            <SelectItem value="forever">{__('freelance.mute_until_i_turn_it_back_on')}</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    {isMuted && !data.mute_duration && (
                                        <div className="mt-3 text-sm text-amber-600 bg-amber-50 p-3 rounded-md border border-amber-200">
                                            {__('freelance.notifications_are_currently_muted_until', {
                                                date: new Date(profile.notifications_muted_until).toLocaleString()
                                            })}
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="pt-4 flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {__('general.save')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Smartphone className="w-5 h-5 text-indigo-600" />
                            <CardTitle>iOS Shortcut Notifications</CardTitle>
                        </div>
                        <CardDescription>
                            {__('general.since_iphones_do_not_support_web_push_no')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                            <h3 className="text-indigo-900 font-semibold mb-2">{__('general.setup_guide')}</h3>
                            <ol className="list-decimal list-inside text-sm text-indigo-800 space-y-2">
                                <li>{__('general.click_the_button_below_to_generate_your')}</li>
                                <li>{__('general.copy_the_generated_token')}</li>
                                <li><a href="#" className="underline font-medium">{__('general.download_the_ios_shortcut')}</a> (iCloud Link).</li>
                                <li>{__('general.when_installing_the_shortcut_on_your_iph')}</li>
                                <li>Set up an iOS Personal Automation (in the Shortcuts app) to run this shortcut automatically (e.g. at 9 AM, or when you open Chrome).</li>
                            </ol>
                        </div>

                        {flash?.ios_shortcut_token && (
                            <div className="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                                <Label className="text-green-900 font-bold">Your API Token (Copy this now):</Label>
                                <div className="mt-2 p-3 bg-white border border-green-300 rounded font-mono text-sm break-all select-all">
                                    {flash.ios_shortcut_token}
                                </div>
                                <p className="text-xs text-green-700 mt-2">{__('general.this_token_will_not_be_shown_again_keep')}</p>
                            </div>
                        )}

                        <div className="pt-2">
                            <Button 
                                variant="outline" 
                                className="border-indigo-200 text-indigo-700 hover:bg-indigo-50"
                                onClick={() => {
                                    setGeneratingToken(true);
                                    router.post(route('freelance.settings.notifications.shortcut-token'), {}, {
                                        onFinish: () => setGeneratingToken(false),
                                    });
                                }}
                                disabled={generatingToken}
                            >
                                {generatingToken ? 'Generating...' : 'Generate API Token'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </FreelanceLayout>
    );
}
