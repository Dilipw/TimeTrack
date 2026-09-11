<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNING = 'planning';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}