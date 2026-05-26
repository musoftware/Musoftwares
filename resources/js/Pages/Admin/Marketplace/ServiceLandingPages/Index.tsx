import React from "react";
import { Head, Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import { Eye, Trash2, Edit } from "lucide-react";

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
}

export default function Index({ servicesWithLandingPages, filters }: Props) {
    const toggleStatus = (id: number) => {
        router.post(route("admin.service-landing-pages.toggle-status", id), {}, { preserveScroll: true });
    };

    const deletePage = (id: number) => {
        if (confirm("Are you sure you want to delete this landing page?")) {
            router.delete(route("admin.service-landing-pages.destroy", id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Service Landing Pages</h2>}
        >
            <Head title="Service Landing Pages" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Manage Landing Pages</h3>
                            {/* You can add a search input here utilizing the `filters` prop */}
                        </div>

                        <div className="border rounded-md">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Service</TableHead>
                                        <TableHead>Seller</TableHead>
                                        <TableHead>Hero Title</TableHead>
                                        <TableHead>Views / A/B</TableHead>
                                        <TableHead>Leads</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {servicesWithLandingPages.data.length > 0 ? (
                                        servicesWithLandingPages.data.map((service) => (
                                            <TableRow key={service.id}>
                                                <TableCell className="font-medium">
                                                    {service.title}
                                                    <br />
                                                    <a href={`/l/${service.landing_page.slug}`} target="_blank" className="text-xs text-blue-500 hover:underline">
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
                                                            {service.landing_page.variants.length} Variants
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-500 text-sm">No A/B Test</span>
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
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <a href={`/l/${service.landing_page.slug}`} target="_blank">
                                                            <Button variant="outline" size="icon">
                                                                <Eye className="w-4 h-4" />
                                                            </Button>
                                                        </a>
                                                        <Button variant="outline" size="icon" className="text-red-600 hover:text-red-700 hover:bg-red-50" onClick={() => deletePage(service.landing_page.id)}>
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center h-24 text-gray-500">
                                                No landing pages found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
