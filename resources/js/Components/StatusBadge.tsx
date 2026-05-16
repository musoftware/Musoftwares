import { Badge } from '@/components/ui/badge';

type StatusType =
    | 'pending'
    | 'active'
    | 'completed'
    | 'cancelled'
    | 'failed'
    | 'processing'
    | 'draft'
    | 'published';

interface StatusBadgeProps {
    status: string | StatusType;
}

export default function StatusBadge({ status }: StatusBadgeProps) {
    const statusStr = String(status).toLowerCase();

    let variant: 'default' | 'secondary' | 'destructive' | 'outline' =
        'default';
    let customClass = '';

    switch (statusStr) {
        case 'active':
        case 'completed':
        case 'published':
            variant = 'default';
            customClass =
                'bg-green-100 text-green-800 hover:bg-green-100 border-transparent';
            break;
        case 'pending':
        case 'processing':
            variant = 'secondary';
            customClass = 'bg-yellow-100 text-yellow-800 hover:bg-yellow-100';
            break;
        case 'cancelled':
        case 'failed':
            variant = 'destructive';
            break;
        case 'draft':
            variant = 'outline';
            break;
        default:
            variant = 'secondary';
    }

    return (
        <Badge variant={variant} className={customClass}>
            {statusStr.charAt(0).toUpperCase() + statusStr.slice(1)}
        </Badge>
    );
}
