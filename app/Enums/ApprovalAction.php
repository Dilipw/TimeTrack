<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}