<?php

namespace App\Enum;

enum Remarks:string
{
    case MISSING_CHECKOUT = 'missing_checkout';
    case MISSING_CHECKIN = 'missing_checkin';
    case CONFLICTING_CHECKIN_SEQUENCE = 'conflicting_checkin_sequence';

}
