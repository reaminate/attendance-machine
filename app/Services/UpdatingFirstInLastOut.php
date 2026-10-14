<?php

namespace App\Services;

use App\Enum\RecordAction;
use App\Enum\Remarks;
use App\Enum\Status;
use App\Models\Attendance;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UpdatingFirstInLastOut
{
    public const int DUPLICATE_TOLERANCE_SECONDS = 120;

    public const int MINIMUM_OVERTIME_MINUTES = 30;

    public const int LOG_WINDOW_HOURS = 4;

    // loads the employee's raw logs that fall around the assigned shift
    public function eventsFor(EmployeeShiftAssignment $assignment, ShiftWindow $window): Collection
    {
        $from = $window->start->subHours(self::LOG_WINDOW_HOURS);
        $to = $window->end->addHours(self::LOG_WINDOW_HOURS);

        return Attendance::where('employee_id', $assignment->employee_id)
            ->whereBetween('logged_at', [$from->format('Y-m-d H:i'), $to->format('Y-m-d H:i:s')])
            ->orderBy('logged_at')->orderBy('id')->get()
            ->filter(fn (Attendance $event) => $event->logged_at->betweenIncluded($from, $to))
            ->values();
    }

    // removing dupes that occur within two min of the first retained same-action event
    public function removingDupes(Collection $events): array
    {
        $sorted = $events->sortBy([
            fn ($a, $b) => $a->logged_at <=> $b->logged_at,
            fn ($a, $b) => $a->id <=> $b->id,
        ]);
        $kept = collect();
        $anchor = null;
        $duplicatesFound = false;

        foreach ($sorted as $event) {
            $isDuplicate = $anchor !== null
                && $event->action === $anchor->action
                && $anchor->logged_at->diffInSeconds($event->logged_at) <= self::DUPLICATE_TOLERANCE_SECONDS;

            if ($isDuplicate) {
                $duplicatesFound = true;

                continue;
            }

            $kept->push($event);
            $anchor = $event;
        }

        return ['events' => $kept, 'duplicates_ignored' => $duplicatesFound];
    }

    // pairs IN followed by OUT into sessions and flags missing or conflicting events
    public function pairSessions(Collection $events): array
    {
        $sessions = [];
        $remarks = [];
        $openIn = null;
        $previous = null;

        foreach ($events as $event) {
            if ($event->action === RecordAction::IN) {
                if ($previous?->action === RecordAction::IN) {
                    $remarks[] = Remarks::CONFLICTING_CHECKIN_SEQUENCE;
                }
                $openIn = $event;
            } else {
                if ($previous === null) {
                    $remarks[] = Remarks::MISSING_CHECKIN;
                } elseif ($previous->action === RecordAction::OUT) {
                    $remarks[] = Remarks::CONFLICTING_CHECKOUT_SEQUENCE;
                } elseif ($openIn !== null) {
                    $sessions[] = ['in' => $openIn->logged_at, 'out' => $event->logged_at];
                    $openIn = null;
                }
            }
            $previous = $event;
        }

        if ($previous?->action === RecordAction::IN) {
            $remarks[] = Remarks::MISSING_CHECKOUT;
        }

        return [
            'sessions' => $sessions,
            'remarks' => array_values(array_unique($remarks, SORT_REGULAR)),
            'first_in' => $events->first(fn ($e) => $e->action === RecordAction::IN)?->logged_at,
            'last_out' => $events->last(fn ($e) => $e->action === RecordAction::OUT)?->logged_at,
        ];
    }

    // net worked minutes with the scheduled break deducted only once
    public function workedMinutes(array $sessions, Shift $shift): int
    {
        $gross = 0;
        $gaps = 0;
        foreach ($sessions as $i => $session) {
            $gross += (int) $session['in']->diffInMinutes($session['out']);
            if ($i > 0) {
                $gaps += max(0, (int) $sessions[$i - 1]['out']->diffInMinutes($session['in']));
            }
        }
        $break = (int) $shift->break_minutes;
        $remainingBreak = max(0, $break - min($break, $gaps));

        return max(0, $gross - $remainingBreak);
    }

    // minutes after scheduled start beyond the grace allowance
    public function lateMinutes(CarbonInterface $firstIn, ShiftWindow $window, Shift $shift): int
    {
        return max(0, (int) $window->start->diffInMinutes($firstIn) - (int) $shift->grace_minutes);
    }

    // minutes between the last OUT and the scheduled end
    public function earlyDepartureMinutes(CarbonInterface $lastOut, ShiftWindow $window): int
    {
        return max(0, (int) $lastOut->diffInMinutes($window->end));
    }

    // confirmed session time after scheduled end, zero when below the minimum
    public function overtimeMinutes(array $sessions, ShiftWindow $window): int
    {
        $eligible = 0;
        foreach ($sessions as $session) {
            $from = $session['in']->max($window->end);
            $eligible += max(0, (int) $from->diffInMinutes($session['out']));
        }

        return $eligible >= self::MINIMUM_OVERTIME_MINUTES ? $eligible : 0;
    }

    // works out the attendance record values for one shift assignment
    public function evaluate(EmployeeShiftAssignment $assignment): array
    {
        $shift = $assignment->shift;
        $window = ShiftWindow::for($shift, $assignment->shift_date);

        return $this->calculate($this->eventsFor($assignment, $window), $shift, $window);
    }

    // pure calculation 
    public function calculate(Collection $events, Shift $shift, ShiftWindow $window): array
    {
        $deduped = $this->removingDupes($events);
        $events = $deduped['events'];

        if ($events->isEmpty()) {
            return [
                'first_in' => null,
                'last_out' => null,
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'early_departure_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => Status::ABSENT,
                'remarks' => [Remarks::NO_ATTENDANCE_LOGS->value],
            ];
        }

        $paired = $this->pairSessions($events);
        $remarks = $paired['remarks'];
        if ($deduped['duplicates_ignored']) {
            $remarks[] = Remarks::DUPLICATE_LOGS_IGNORED;
        }

        $hasConflict = in_array(Remarks::CONFLICTING_CHECKIN_SEQUENCE, $remarks, true)
            || in_array(Remarks::CONFLICTING_CHECKOUT_SEQUENCE, $remarks, true);
        $isMissing = in_array(Remarks::MISSING_CHECKIN, $remarks, true)
            || in_array(Remarks::MISSING_CHECKOUT, $remarks, true);

        $metrics = [
            'worked_minutes' => null,
            'late_minutes' => null,
            'early_departure_minutes' => null,
            'overtime_minutes' => null,
        ];
        if ($hasConflict) {
            $status = Status::NEEDS_REVIEW;
        } elseif ($isMissing) {
            $status = Status::INCOMPLETE;
        } else {
            $metrics = [
                'worked_minutes' => $this->workedMinutes($paired['sessions'], $shift),
                'late_minutes' => $this->lateMinutes($paired['first_in'], $window, $shift),
                'early_departure_minutes' => $this->earlyDepartureMinutes($paired['last_out'], $window),
                'overtime_minutes' => $this->overtimeMinutes($paired['sessions'], $window),
            ];
            $status = match (true) {
                $metrics['worked_minutes'] >= $shift->minimum_full_day_minutes => Status::PRESENT,
                $metrics['worked_minutes'] >= $shift->minimum_half_day_minutes => Status::HALF_DAY,
                default => Status::INSUFFICIENT_HOURS,
            };
        }

        return [
            'first_in' => $paired['first_in'],
            'last_out' => $paired['last_out'],
            ...$metrics,
            'status' => $status,
            'remarks' => array_map(fn (Remarks $remark) => $remark->value, $remarks),
        ];
    }
}
