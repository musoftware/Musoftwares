import { usePage } from '@inertiajs/react';
import { Key, Monitor } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section } from '@/Components/Client/ClientPage';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface SoftwareLicense { id: number; software_name: string; status: string; expires_at: string | null; max_devices: number; created_at: string | null }
interface LinkedDevice { id: number; device_id: string; status: string; expires_at: string | null; machine_name?: string | null; os_version?: string | null; last_check_date?: string | null; software_name?: string | null }

/** The client's program licenses and the computers they are activated on. */
export default function MyLicenses({ licenses = [], linkedDevices = [] }: { licenses: SoftwareLicense[]; linkedDevices: LinkedDevice[] }) {
    const { locale } = usePage().props as any;
    const loc = locale === 'ar' ? 'ar-EG' : 'en-GB';
    const formatDate = (d: string | null) => (d ? new Date(d).toLocaleDateString(loc, { day: 'numeric', month: 'long', year: 'numeric' }) : '');
    const statusText = (active: boolean) => (
        <span className={cn('text-sm font-semibold', active ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600')}>
            {active ? __('general.active') : __('general.inactive')}
        </span>
    );

    return (
        <ClientPage
            title={__('tools.store_my_licenses_title')}
            hint={__('tools.store_my_licenses_desc')}
            action={<PrimaryAction href={route('store.tools.index')}>{__('tools.store_browse_more')}</PrimaryAction>}
        >
            <Section title={__('tools.store_active_licenses_count', { count: licenses.length })}>
                {licenses.length === 0 ? (
                    <EmptyNote icon={<Key className="h-5 w-5" />}>{__('tools.store_no_licenses')} {__('tools.store_no_licenses_hint')}</EmptyNote>
                ) : (
                    <ul className="grid grid-cols-1 gap-3 md:grid-cols-2">
                        {licenses.map((lic) => (
                            <li key={lic.id}>
                                <Card>
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h3 className="font-bold">{lic.software_name}</h3>
                                            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400">{__('tools.store_license_number', { id: lic.id })}</p>
                                        </div>
                                        {statusText(lic.status === 'active')}
                                    </div>
                                    <dl className="mt-3 space-y-1.5 border-t border-black/5 pt-3 text-sm dark:border-white/10">
                                        <div className="flex justify-between gap-3"><dt className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('tools.store_allowed_devices')}</dt><dd className="font-semibold">{__('tools.store_computers_count', { count: lic.max_devices })}</dd></div>
                                        <div className="flex justify-between gap-3"><dt className="text-[#1d1d1f]/60 dark:text-zinc-400">{__('tools.store_expires_on')}</dt><dd className="font-semibold">{lic.expires_at ? formatDate(lic.expires_at) : __('general.lifetime')}</dd></div>
                                    </dl>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title={__('tools.store_connected_devices_count', { count: linkedDevices.length })}>
                {linkedDevices.length === 0 ? (
                    <EmptyNote icon={<Monitor className="h-5 w-5" />}>{__('tools.store_no_devices')}</EmptyNote>
                ) : (
                    <ul className="space-y-2">
                        {linkedDevices.map((dev) => (
                            <li key={dev.id}>
                                <Card className="flex flex-wrap items-center justify-between gap-3">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <Monitor className="h-5 w-5 shrink-0 text-[#0071e3]" />
                                        <div className="min-w-0">
                                            <p className="font-semibold">{dev.machine_name || __('tools.store_windows_pc')}{dev.software_name ? ` · ${dev.software_name}` : ''}</p>
                                            <p className="truncate text-xs text-[#1d1d1f]/50 dark:text-zinc-400">
                                                <span dir="ltr" className="font-mono">{dev.device_id}</span> · {dev.os_version || 'Windows'}
                                            </p>
                                            <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-400">
                                                {__('tools.store_expiration_date')}: {dev.expires_at ? formatDate(dev.expires_at) : __('tools.store_permanent')}
                                                {' · '}{__('tools.store_last_check_in')}: {dev.last_check_date ? new Date(dev.last_check_date).toLocaleString(loc) : __('tools.store_recently')}
                                            </p>
                                        </div>
                                    </div>
                                    {statusText(dev.status === 'active')}
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section title={__('tools.store_how_activation_works')}>
                <Card>
                    <ol className="list-inside list-decimal space-y-1.5 text-sm text-[#1d1d1f]/75 dark:text-zinc-300">
                        <li>{__('tools.store_how_step1')}</li>
                        <li>{__('tools.store_how_step2')}</li>
                        <li>{__('tools.store_how_step3')}</li>
                    </ol>
                </Card>
            </Section>
        </ClientPage>
    );
}
