<?php

namespace App\Enum;

enum TicketCategory: string
{
    case TECHNICAL = 'technical';
    case BILLING = 'billing';
    case FEATURE_REQUEST = 'feature_request';
    case ADMIN = 'admin';
}
