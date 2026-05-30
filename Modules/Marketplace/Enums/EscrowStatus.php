<?php

namespace Modules\Marketplace\Enums;

enum EscrowStatus: string
{
    case HELD = 'held';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';
}
