<?php

namespace App\Enums;

enum TimeEntryType: string
{
    case REGULAR = 'regular';
    case OVERTIME = 'overtime';
}