<?php

namespace App\Services\Academics;

use Carbon\Carbon;

class TermCalendar
{
    /**
     * Suggest Term I–III with teaching blocks and holiday gaps — not equal thirds of the year.
     *
     * @return list<array{name:string,sequence:int,starts_on:string,ends_on:string}>
     */
    public function suggestThreeTerms(string $startsOn, string $endsOn): array
    {
        $start = Carbon::parse($startsOn)->startOfDay();
        $end = Carbon::parse($endsOn)->startOfDay();
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $termDays = 13 * 7;
        $holidayDays = 18;

        $t1Start = $start->copy();
        $t1End = $t1Start->copy()->addDays($termDays - 1);
        $t2Start = $t1End->copy()->addDays($holidayDays + 1);
        $t2End = $t2Start->copy()->addDays($termDays - 1);
        $t3Start = $t2End->copy()->addDays($holidayDays + 1);
        $t3End = $end->copy();

        if ($t3Start->gt($end) || $t2End->gt($end)) {
            $span = max(21, $start->diffInDays($end) + 1);
            $gap = 7;
            $usable = max(9, $span - (2 * $gap));
            $chunk = (int) floor($usable / 3);

            $t1Start = $start->copy();
            $t1End = $t1Start->copy()->addDays($chunk - 1);
            $t2Start = $t1End->copy()->addDays($gap + 1);
            $t2End = $t2Start->copy()->addDays($chunk - 1);
            $t3Start = $t2End->copy()->addDays($gap + 1);
            $t3End = $end->copy();
        }

        $clamp = function (Carbon $date) use ($start, $end): Carbon {
            if ($date->lt($start)) {
                return $start->copy();
            }
            if ($date->gt($end)) {
                return $end->copy();
            }

            return $date;
        };

        $t1End = $clamp($t1End);
        $t2Start = $clamp($t2Start);
        $t2End = $clamp($t2End);
        $t3Start = $clamp($t3Start);

        if ($t2Start->lt($t1End)) {
            $t2Start = $t1End->copy();
        }
        if ($t3Start->lt($t2End)) {
            $t3Start = $t2End->copy();
        }

        return [
            ['name' => 'Term I', 'sequence' => 1, 'starts_on' => $t1Start->toDateString(), 'ends_on' => $t1End->toDateString()],
            ['name' => 'Term II', 'sequence' => 2, 'starts_on' => $t2Start->toDateString(), 'ends_on' => $t2End->toDateString()],
            ['name' => 'Term III', 'sequence' => 3, 'starts_on' => $t3Start->toDateString(), 'ends_on' => $t3End->toDateString()],
        ];
    }
}
