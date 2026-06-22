import React from "react";
import { Head, Link, router } from "@inertiajs/react";
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import { Eye, Trash2, Edit, MoreHorizontal } from "lucide-react";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { __ } from "@/lib/i18n";

interface LandingPage {
    id: number;
    slug: string;
    hero_title: string;
    is_active: boolean;
    variants: any[];
    formSubmissions: any[];
}

interface Service {
    id: number;
    title: string;
    user: { name: string; email: string };
    landing_page: LandingPage;
}

interface Props {
    servicesWithLandingPages: {
        data: Service[];
        links: any[];
    };
    filters: any;
    auth: {
        user: any;
    };
    translations: Record<string, string>;
}

export default function Index({ servicesWithLandingPages, filters, auth, translations }: Props) {
    const toggleStatus = (id: number) => {
        router.post(route("admin.marketplace.service-landing-pages.toggle-status", id), {}, { preserveScroll: true });
    };

    const deletePage = (id: number) => {
        if (confirm(translations.confirm_delete)) {
            router.delete(route("admin.marketplace.service-landing-pages.destroy", id), { preserveScroll: true });
        }
    };

    return (
        <AdminSidebarLayout title={translations.service_landing_pages} header={translations.service_landing_pages}>
            <Head title={translations.service_landing_pages} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">{translations.service_landing_pages}</h1>
                    <p className="text-sm text-slate-500 mt-1">
                        {translations.manage_all_landing_pages}
                    </p>
                </div>

                <div className="bg-white shadow-sm sm:rounded-xl border border-slate-200 p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-lg font-medium text-gray-900">{translations.manage_landing_pages}</h3>
                        {/* You can add a search input here utilizing the `filters` prop */}
                    </div>

                    <div className="border rounded-md">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{translations.service}</TableHead>
                                        <TableHead>{translations.seller}</TableHead>
                                        <TableHead>{translations.hero_title}</TableHead>
                                        <TableHead>{translations.views_ab}</TableHead>
                                        <TableHead>{translations.leads}</TableHead>
                                        <TableHead>{translations.status}</TableHead>
                                        <TableHead className="text-end">{translations.actions}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(servicesWithLandingPages.data as any).length > 0 ? (
                                        (servicesWithLandingPages.data as any).map((service) => (
                                            <TableRow key={service.id}>
                                                <TableCell className="font-medium">
                                                    {service.title}
                                                    <br />
                                                    <a href={`/l/${service.landing_page.slug}`} target="_blank" className="text-xs text-slate-900 hover:underline" rel="noreferrer">
                                                        /l/{service.landing_page.slug}
                                                    </a>
                                                </TableCell>
                                                <TableCell>
                                                    {service.user?.name}
                                                    <div className="text-xs text-gray-500">{service.user?.email}</div>
                                                </TableCell>
                                                <TableCell>{service.landing_page.hero_title}</TableCell>
                                                <TableCell>
                                                    {service.landing_page.variants?.length > 0 ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            {service.landing_page.variants.length} {translations.variants}
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-500 text-sm">{translations.no_ab_test}</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {service.landing_page.formSubmissions?.length || 0}
                                                </TableCell>
                                                <TableCell>
                                                    <Switch
                                                        checked={service.landing_page.is_active}
                                                        onCheckedChange={() => toggleStatus(service.landing_page.id)}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">{translations.actions}</span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <a href={`/l/${service.landing_page.slug}`} target="_blank" rel="noreferrer" className="cursor-pointer flex items-center">
                                                                    <Eye className="w-4 h-4 me-2" />
                                                                    {translations.view}
                                                                </a>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => deletePage(service.landing_page.id)} className="text-red-600 focus:text-red-700 cursor-pointer flex items-center">
                                                                <Trash2 className="w-4 h-4 me-2" />
                                                                {translations.delete}
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center h-24 text-gray-500">
                                                {translations.no_landing_pages_found}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
        </AdminSidebarLayout>
    );
}
