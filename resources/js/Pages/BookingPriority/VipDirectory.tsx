import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Plus } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function VipDirectory({ vipCustomers }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.vip_directory')}</h1>
                    <p className="text-muted-foreground mt-2">{__('general.manage_premium_customer_tiers_and_priority_tagging')}</p>
                </div>
                <Button>
                    <Plus className="me-2 h-4 w-4" />{__('general.add_vip')}</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{__('general.premium_customers')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer</TableHead>
                                <TableHead>Tier</TableHead>
                                <TableHead>{__('general.assigned_date')}</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead className="text-end">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vipCustomers.map((vip) => (
                                <TableRow key={vip.id}>
                                    <TableCell className="font-medium">{vip.customer_name}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" style={{ borderColor: vip.level.color, color: vip.level.color }}>
                                            {vip.level.name}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{new Date(vip.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell className="text-muted-foreground">{vip.reason}</TableCell>
                                    <TableCell className="text-end">
                                        <Button variant="ghost" size="sm">Manage</Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
