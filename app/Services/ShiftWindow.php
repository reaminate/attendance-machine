<?php

namespace App\Services;

use App\Models\Shift;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

// for shift window
final readonly class ShiftWindow
{
    public function __construct(public CarbonImmutable $start, public CarbonImmutable $end) {}

    public static function for(Shift $shift, CarbonInterface $shiftDate): self
    {
        $date = $shiftDate->toDateString();
        $start = CarbonImmutable::parse("{$date} {$shift->start_time}");
        $end = CarbonImmutable::parse("{$date} {$shift->end_time}");

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return new self($start, $end);
    }
}
