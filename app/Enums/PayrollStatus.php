<?php

declare(strict_types=1);

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
}