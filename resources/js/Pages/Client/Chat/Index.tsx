import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Plus } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Rule {
    id: number;
    name: string;
    target_metric: string;
    is_active: boolean;
}

export default function SmartRules({ rules }: { rules: Rule[] }) {
    return (
        <div className="p-6 max-w-7xl mx-auto space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">{__('general.smart_rules_engine')}</h1>
                    <p className="text-muted-foreground mt-2">{__('general.configure_rules_for_gap_reduction_and_load_balancing')}</p>
                </div>
                <Button>
                    <Plus className="me-2 h-4 w-4" />{__('general.add_rule')}</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{__('general.active_optimization_rules')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{__('general.rule_name')}</TableHead>
                                <TableHead>{__('general.target_metric')}</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-end">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rules.map((rule) => (
                                <TableRow key={rule.id}>
                                    <TableCell className="font-medium">{rule.name}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{rule.target_metric}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={rule.is_active ? 'default' : 'secondary'}>
                                            {rule.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-end">
                                        <Button variant="ghost" size="sm">Edit</Button>
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

