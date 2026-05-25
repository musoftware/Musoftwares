import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Plus } from 'lucide-react';

export default function VipDirectory({ vipCustomers }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">VIP Directory</h1>
                    <p className="text-muted-foreground mt-2">Manage premium customer tiers and priority tagging.</p>
                </div>
                <Button>
                    <Plus className="mr-2 h-4 w-4" /> Add VIP
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Premium Customers</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer</TableHead>
                                <TableHead>Tier</TableHead>
                                <TableHead>Assigned Date</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
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
                                    <TableCell className="text-right">
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
