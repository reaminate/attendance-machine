<?php

namespace App\Enum;

enum Remarks: string
{
    case MISSING_CHECKOUT = 'missing_checkout';
    case MISSING_CHECKIN = 'missing_checkin';
    case CONFLICTING_CHECKIN_SEQUENCE = 'conflicting_checkin_sequence';
    case DUPLICATE_LOGS_IGNORED = 'duplicate_logs_ignored';
    case NO_ATTENDANCE_LOGS = 'no_attendance_logs';
    case CONFLICTING_CHECKOUT_SEQUENCE = 'conflicting_checkout_sequence';
}
