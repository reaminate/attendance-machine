# Attendance Machine Data Adder

This application is to make it easy to input data into a simplistic table, with the following buissness rules and calculation policy.
## Business rules

1. Normal attendance
For a 09:00–17:00 shift, IN 09:00 and OUT 17:00: pair the events; subtract the single 60-minute break; classify by the full-day threshold. There should be no lateness, early departure, or overtime.

2. Late arrival
For IN 09:25 and OUT 17:00, calculate actual delay relative to 09:00 and subtract 10 minutes of grace for the **late metric**. Worked time is still based on actual punches, less the break. The attendance status must follow the worked-time threshold, not automatically become `late`.

3. Early departure
For IN 09:00 and OUT 16:30, calculate minutes between final OUT and 17:00, and calculate net worked minutes. Apply the configured status threshold independently.

4. Late arrival and early departure
For IN 09:30 and OUT 16:30, calculate both lateness (after grace) and early departure, and net work. Neither metric should overwrite the other.

5. Arrival within grace
For IN 09:10 and OUT 17:00, the employee is within the 10-minute grace allowance. Late minutes must be zero, but the actual shorter duration is still used for worked minutes and status.

6. Missing checkout
For a single IN 09:00 with no OUT, do not invent an OUT at shift end. Mark `incomplete`, retain the observed IN, use null for unverified minute metrics, and include `missing_checkout` in remarks.

7. Missing check-in
For a single OUT 17:00 with no IN, do not invent an IN at shift start. Mark `incomplete`, retain the observed OUT, use null for unverified minute metrics, and include `missing_checkin` in remarks.

8. Multiple sessions and unpaid break
For IN 09:00, OUT 12:00, IN 13:00, OUT 17:00, pair two sessions, sum their durations, and account for the one-hour between-session gap as the scheduled break. Do not deduct a second 60-minute break.

9. Duplicate machine events
For IN 09:00, IN 09:01, OUT 17:00, OUT 17:01, remove duplicate same-action events within two minutes before pairing. Preserve the raw data and record a `duplicate_logs_ignored` flag.

10. Overnight shift
For a shift assigned on 2026-10-01 from 22:00–06:00, pair IN on 2026-10-01 at 22:00 with OUT on 2026-10-02 at 06:00. The resulting `shift_date` remains 2026-10-01. Calculate net worked minutes across midnight.

11. Half-day attendance
For IN 09:00 and OUT 14:00, deduct the configured break once and compare the result to the half-/full-day thresholds. Early departure is measured against 17:00.

12. Absence
When an employee has a shift assignment but no associated machine events, generate an `absent` record with zero worked/late/early/overtime minutes and null IN/OUT.

13. Post-shift overtime
For IN 09:00 and OUT 18:30, count the confirmed portion of the session after 17:00 as overtime (subject to the 30-minute minimum). Net worked time includes this period, and the break is deducted only once.

14. Early arrival is not overtime
For IN 08:30 and OUT 17:00, preserve 08:30 as actual IN, count the confirmed early work in total net worked minutes, but set overtime to zero because no work occurred after 17:00.

5. Conflicting log sequence
For IN 09:00, IN 11:00, OUT 17:00, the second IN is beyond the duplicate tolerance. Do not silently pair events or invent a missing OUT. Mark `needs_review`, use null for unverified metrics, and include `conflicting_checkin_sequence` in remarks.

## Policies

1. Convert `shift_date` + `start_time`/`end_time` into local datetimes. If end time is **at or before** start time, end is on the next calendar day. Store the result under the **shift start date**.

2. Sort events by `logged_at`, then `id` as a deterministic tie-breaker. Do not modify source logs.

3. Before pairing, remove only consecutive same-action events that occur within **2 minutes of the first retained event**; retain the earliest event. Events beyond the tolerance are conflicts, not duplicates. Apply to both IN and OUT.

4. Valid work sessions are `IN` followed by `OUT`. Sum completed session durations. Unpaired or conflicting events make the overall record `incomplete` or `needs_review` as defined below.

5. **Break calculation:** Let `gross_minutes` be the sum of valid IN→OUT session durations. Let `gaps_minutes` be the sum of positive intervals between successive completed sessions. Set `remaining_break = max(0, shift.break_minutes - min(shift.break_minutes, gaps_minutes))`. Set `worked_minutes = max(0, gross_minutes - remaining_break)`. This avoids double deduction for split sessions and uses one scheduled unpaid break for a continuous session. For this exam, apply this break rule even if the shift is only partially attended.

6. **Early arrival:** Count time from the actual first IN in `worked_minutes`, including pre-shift time; it does **not** create overtime. Overtime is only confirmed IN→OUT work **after** scheduled shift end, with a minimum of 30 eligible minutes. If eligible overtime is below 30 minutes, overtime is 0; otherwise use all eligible overtime minutes. Do not subtract the scheduled break twice from overtime. `worked_minutes` already includes overtime.

7. `late_minutes = max(0, minutes(first valid IN - scheduled start) - grace_minutes)`. Grace waives late penalties, **not** missing worked time.

8. `early_departure_minutes = max(0, minutes(scheduled end - last valid OUT))` for reliable completed attendance. An early OUT is not automatically absence.

9. For complete, non-conflicting attendance, compare `worked_minutes` with the shift's `minimum_full_day_minutes` and `minimum_half_day_minutes`: `present` if >= full, `half_day` if >= half but below full, otherwise `insufficient_hours`. Late/early/overtime are separate metrics, not statuses.

10. `absent` means an assigned shift with **zero** associated events; use 0 for all minute metrics, null for IN/OUT, and a remark. Never generate absences for employees without a shift assignment.

11. `incomplete` means a missing IN or OUT in an otherwise non-conflicting sequence. `needs_review` means a nonduplicate conflicting sequence (e.g., IN, IN, OUT). In both cases, preserve reliable first/last observed timestamps where available, but set **all calculated minute metrics to null** rather than guessing. Record machine-readable reasons in `remarks`.

12. No leave, public holidays, rest days, approvals, overtime preapproval, or attendance correction data are provided. Do not invent such records or rules.

## Tests

There are 15 tests, each for the different buisness rules. They each assert the timestamps and numeric calculations, to make sure everything is correct.

## Exceution

For this, there are two custom `php artisan` commands.

1. `attendance:generate {from} {to}` To run this command, you will need to input both the `{from}` and `{to}` in the format `yyyy-mm-dd`. When run, this will add/update the records to the `attendance_records`table based on the records in `employee_shift_assignments`table, `attendances`table, and show a summary of the data along with their records. Those two inputs are a must. There is an optional `--employee={id}` input you can do, to make it generate only for that employee. The inputed `{id}` should be the primary key of that employee. You can input multiple employee `{id}`s.
- Following is run with no optional `{--employee=}`
```json
{
    "summary": {
        "processed": 15,
        "created": 0,
        "updated": 15,
        "insufficient_hours": 1,
        "absent": 1,
        "incomplete": 2,
        "needs_review": 1,
        "failed": 0,
        "errors": []
    },
    "records": [
        {
            "id": 1,
            "employee_id": 1,
            "shift_id": 1,
            "shift_date": "2026:10:01",
            "first_in": "09:00",
            "last_out": "2026:10:01 17:00",
            "worked_minutes": 420,
            "late_minutes": 0,
            "early_departure_minutes": 0,
            "overtime_minutes": 0,
            "status": "present",
            "remarks": [],
            "created_at": "2026-10-15T06:44:03.000000Z",
            "updated_at": "2026-10-15T06:44:03.000000Z"
        },
        {
            "id": 2,
            "employee_id": 2,
            "shift_id": 1,
            "shift_date": "2026:10:01",
            "first_in": "09:25",
            "last_out": "2026:10:01 17:00",
            "worked_minutes": 395,
            "late_minutes": 15,
            "early_departure_minutes": 0,
            "overtime_minutes": 0,
            "status": "half_day",
            "remarks": [],
            "created_at": "2026-10-15T06:44:03.000000Z",
            "updated_at": "2026-10-15T06:44:03.000000Z"
        },
        ............
        ............
        {
            "id": 15,
            "employee_id": 15,
            "shift_id": 1,
            "shift_date": "2026:10:01",
            "first_in": "09:00",
            "last_out": "2026:10:01 17:00",
            "worked_minutes": null,
            "late_minutes": null,
            "early_departure_minutes": null,
            "overtime_minutes": null,
            "status": "needs_review",
            "remarks": [
                "conflicting_checkin_sequence"
            ],
            "created_at": "2026-10-15T06:44:04.000000Z",
            "updated_at": "2026-10-15T06:44:04.000000Z"
        }
    ]
}
```
- Following is run with one employee
```json
{
    "summary": {
        "processed": 1,
        "created": 0,
        "updated": 1,
        "insufficient_hours": 0,
        "absent": 0,
        "incomplete": 0,
        "needs_review": 0,
        "failed": 0,
        "errors": []
    },
    "records": [
        {
            "id": 1,
            "employee_id": 1,
            "shift_id": 1,
            "shift_date": "2026:10:01",
            "first_in": "09:00",
            "last_out": "2026:10:01 17:00",
            "worked_minutes": 420,
            "late_minutes": 0,
            "early_departure_minutes": 0,
            "overtime_minutes": 0,
            "status": "present",
            "remarks": [],
            "created_at": "2026-10-15T06:44:03.000000Z",
            "updated_at": "2026-10-15T06:44:03.000000Z"
        }
    ]
}
```
- Following is run with two employees
```json
{
    "summary": {
        "processed": 2,
        "created": 0,
        "updated": 2,
        "insufficient_hours": 0,
        "absent": 0,
        "incomplete": 0,
        "needs_review": 0,
        "failed": 0,
        "errors": []
    },
    "records": [
        {
            "id": 1,
            "employee_id": 1,
            "shift_id": 1,
            "shift_date": "2026:10:01",
            "first_in": "09:00",
            "last_out": "2026:10:01 17:00",
            "worked_minutes": 420,
            "late_minutes": 0,
            "early_departure_minutes": 0,
            "overtime_minutes": 0,
            "status": "present",
            "remarks": [],
            "created_at": "2026-10-15T06:44:03.000000Z",
            "updated_at": "2026-10-15T06:44:03.000000Z"
        },
        {
            "id": 2,
            "employee_id": 2,
            "shift_id": 1,
            "shift_date": "2026:10:01",
            "first_in": "09:25",
            "last_out": "2026:10:01 17:00",
            "worked_minutes": 395,
            "late_minutes": 15,
            "early_departure_minutes": 0,
            "overtime_minutes": 0,
            "status": "half_day",
            "remarks": [],
            "created_at": "2026-10-15T06:44:03.000000Z",
            "updated_at": "2026-10-15T06:44:03.000000Z"
        }
    ]
}
```

2. `attendance:list` There is no additional input needed to run this command. When run, this will just return you the records already in the `attendance_records` table with no summary or changing of records. There is optional `{--from=}`, `{--to=}` (refering to the shift date) and `{--employee=}` when running this command. Just like previous command, you can run the `{--employee=}` with multiple employees.
- Following is run with no options
```json
[
    {
        "id": 1,
        "employee_id": 1,
        "shift_id": 1,
        "shift_date": "2026:10:01",
        "first_in": "09:00",
        "last_out": "2026:10:01 17:00",
        "worked_minutes": 420,
        "late_minutes": 0,
        "early_departure_minutes": 0,
        "overtime_minutes": 0,
        "status": "present",
        "remarks": [],
        "created_at": "2026-10-15T06:44:03.000000Z",
        "updated_at": "2026-10-15T06:44:03.000000Z"
    },
    {
        "id": 2,
        "employee_id": 2,
        "shift_id": 1,
        "shift_date": "2026:10:01",
        "first_in": "09:25",
        "last_out": "2026:10:01 17:00",
        "worked_minutes": 395,
        "late_minutes": 15,
        "early_departure_minutes": 0,
        "overtime_minutes": 0,
        "status": "half_day",
        "remarks": [],
        "created_at": "2026-10-15T06:44:03.000000Z",
        "updated_at": "2026-10-15T06:44:03.000000Z"
    },
    ...........
    ...........
    {
        "id": 15,
        "employee_id": 15,
        "shift_id": 1,
        "shift_date": "2026:10:01",
        "first_in": "09:00",
        "last_out": "2026:10:01 17:00",
        "worked_minutes": null,
        "late_minutes": null,
        "early_departure_minutes": null,
        "overtime_minutes": null,
        "status": "needs_review",
        "remarks": [
            "conflicting_checkin_sequence"
        ],
        "created_at": "2026-10-15T06:44:04.000000Z",
        "updated_at": "2026-10-15T06:44:04.000000Z"
    }
]
```
- Following is run with two employees
```json
[
    {
        "id": 1,
        "employee_id": 1,
        "shift_id": 1,
        "shift_date": "2026:10:01",
        "first_in": "09:00",
        "last_out": "2026:10:01 17:00",
        "worked_minutes": 420,
        "late_minutes": 0,
        "early_departure_minutes": 0,
        "overtime_minutes": 0,
        "status": "present",
        "remarks": [],
        "created_at": "2026-10-15T06:44:03.000000Z",
        "updated_at": "2026-10-15T06:44:03.000000Z"
    },
    {
        "id": 10,
        "employee_id": 10,
        "shift_id": 2,
        "shift_date": "2026:10:01",
        "first_in": "22:00",
        "last_out": "2026:10:02 06:00",
        "worked_minutes": 420,
        "late_minutes": 0,
        "early_departure_minutes": 0,
        "overtime_minutes": 0,
        "status": "present",
        "remarks": [],
        "created_at": "2026-10-15T06:44:04.000000Z",
        "updated_at": "2026-10-15T06:44:04.000000Z"
    }
]
```

