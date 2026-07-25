<?php

namespace Modules\Marketplace\Enums;

enum ServiceOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING_PAYMENT = 'pending_payment';
    case PENDING_REQUIREMENTS = 'pending_requirements';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case IN_PROGRESS = 'in_progress';
    case NEED_MORE_INFORMATION = 'need_more_information';
    case EXTENSION_REQUESTED = 'extension_requested';
    case DELIVERED = 'delivered';
    case REVISION = 'revision';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case CANCELLATION_REQUESTED = 'cancellation_requested';
    case DISPUTED = 'disputed';
    case REFUNDED = 'refunded';
}
