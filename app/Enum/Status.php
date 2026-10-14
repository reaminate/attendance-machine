<?php

namespace App\Enum;

enum Status: string
{
    case PRESENT = 'present';
    case HALF_DAY = 'half_day';
    case INSUFFICIENT_HOURS = 'insufficient_hours';
    case ABSENT = 'absent';
    case INCOMPLETE = 'incomplete';
    case NEEDS_REVIEW = 'needs_review';
}
