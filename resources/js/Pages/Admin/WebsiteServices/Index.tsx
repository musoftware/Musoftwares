import React from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Plus, MoreHorizontal, Edit, Trash } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

export default function Index({ services }: { services: any[] }) {
    return (
        <AdminSidebarLayout
            header="Website Services"
            actions={
                <Link href={route('admin.website-services.create')}>
                    <Button><Plus className="w-4 h-4 mr-2" /> Add Service</Button>
                </Link>
            }
        >
            <Head title="Website Services" />
            <div className="bg-white rounded-xl shadow-sm border border-slate-200">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Icon/Image</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Subtitle</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {services.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center py-8 text-slate-500">No services found.</TableCell>
                            </TableRow>
                        ) : (
                            services.map((service) => (
                                <TableRow key={service.id}>
                                    <TableCell>
                                        {service.primary_image_en ? (
                                            <img src={`/${service.primary_image_en}`} alt={service.title_en} className="w-10 h-10 rounded object-cover" />
                                        ) : (
                                            <div className="w-10 h-10 rounded bg-slate-100 flex items-center justify-center text-slate-400">N/A</div>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">{service.title_en}</TableCell>
                                    <TableCell>{service.subtitle_en}</TableCell>
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                    <span className="sr-only">Open menu</span>
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('admin.website-services.edit', service.id)} className="flex items-center">
                                                        <Edit className="w-4 h-4 mr-2" /> Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem asChild className="text-red-600 focus:bg-red-50 focus:text-red-700">
                                                    <Link href={route('admin.website-services.destroy', service.id)} method="delete" as="button" className="w-full flex items-center">
                                                        <Trash className="w-4 h-4 mr-2" /> Delete
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </AdminSidebarLayout>
    );
}
