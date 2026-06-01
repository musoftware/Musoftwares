import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { UserPlus, Trash2 } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function AffiliateModeratorsIndex({ moderators }: any) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <Head title={__('general.team_moderators')} />

            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 tracking-tight">{__('general.team_moderators')}</h1>
                    <p className="text-sm text-gray-500 mt-1">{__('general.manage_sub_accounts_for_your_pos_cashiers_and_marketers')}</p>
                </div>
                <Link href={route('affiliate_pos.affiliate.moderators.create')}>
                    <Button className="bg-blue-600 hover:bg-blue-700">
                        <UserPlus className="w-4 h-4 mr-2" />{__('general.add_moderator')}</Button>
                </Link>
            </div>

            <Card className="shadow-sm border-gray-200">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-gray-50/80">
                                <TableHead className="font-semibold text-gray-600">Name</TableHead>
                                <TableHead className="font-semibold text-gray-600">Email</TableHead>
                                <TableHead className="font-semibold text-gray-600">Joined</TableHead>
                                <TableHead className="font-semibold text-gray-600 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {moderators.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-32 text-center text-gray-500">{__('general.no_moderators_found')}</TableCell>
                                </TableRow>
                            ) : (
                                moderators.data.map((mod: any) => (
                                    <TableRow key={mod.id}>
                                        <TableCell className="font-medium text-gray-900">{mod.name}</TableCell>
                                        <TableCell className="text-gray-600">{mod.email}</TableCell>
                                        <TableCell className="text-sm text-gray-500">
                                            {new Date(mod.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="icon" className="text-red-500 hover:text-red-600 hover:bg-red-50">
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
