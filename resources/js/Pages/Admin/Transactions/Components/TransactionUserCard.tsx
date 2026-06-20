import React from 'react';
import { Link } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { ExternalLink } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function TransactionUserCard({ user }) {
    if (!user) return null;

    return (
        <Card className="mb-6 bg-slate-50/50">
            <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    <Avatar className="w-12 h-12 border">
                        <AvatarImage src={user.avatar_url} alt={user.name} />
                        <AvatarFallback>{user.name?.charAt(0)}</AvatarFallback>
                    </Avatar>
                    <div className="flex flex-col">
                        <h3 className="font-semibold text-slate-900">{user.name}</h3>
                        <p className="text-sm text-slate-500">{user.email}</p>
                    </div>
                </div>
                
                <div className="flex flex-col sm:flex-row items-center gap-6">
                    <div className="flex flex-col items-center sm:items-end">
                        <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">
                            {__('general.balance')}
                        </span>
                        <span className="font-mono text-lg font-medium text-slate-900">
                            {formatCurrency(user.available_balance || 0, user.currency)}
                        </span>
                    </div>
                    
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href={`/admin/users/${user.id}`}>
                            {__('general.profile')}
                            <ExternalLink className="w-4 h-4" />
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
