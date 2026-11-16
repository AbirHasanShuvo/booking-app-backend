<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;

class AvailabilityService
{
    const OPEN = '09:00';
    const CLOSE = '18:00';
    const STEP_MINUTES = 30;
    const ACTIVE_STATUSES = ['pending', 'confirmed'];

    public function slotsForDate(Service $service, Carbon $date): array
    {
        [$open, $close] = $this->workingWindow($date);

        $booked = Appointment::where('service_id', $service->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('start_at', '<', $close)
            ->where('end_at', '>', $open)
            ->get(['start_at', 'end_at']);

        $slots = [];

        for ($t = $open->copy(); $t->copy()->addMinutes($service->duration_minutes)->lte($close); $t->addMinutes(self::STEP_MINUTES)) {
            $end = $t->copy()->addMinutes($service->duration_minutes);

            $isPast = $t->lte(now());
            $conflict = $booked->contains(fn ($a) => $a->start_at->lt($end) && $a->end_at->gt($t));

            $slots[] = [
                'start_at'  => $t->format('Y-m-d H:i'),
                'start'     => $t->format('H:i'),
                'end'       => $end->format('H:i'),
                'available' => ! $isPast && ! $conflict,
            ];
        }

        return $slots;
    }

    public function isValidSlot(Service $service, Carbon $start): bool
    {
        [$open, $close] = $this->workingWindow($start);
        $end = $start->copy()->addMinutes($service->duration_minutes);

        return $start->gte($open)
            && $end->lte($close)
            && $open->diffInMinutes($start) % self::STEP_MINUTES === 0;
    }

    public function hasConflict(Service $service, Carbon $start, Carbon $end): bool
    {
        return Appointment::where('service_id', $service->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }

    private function workingWindow(Carbon $date): array
    {
        $day = $date->copy()->startOfDay();

        return [
            $day->copy()->setTimeFromTimeString(self::OPEN),
            $day->copy()->setTimeFromTimeString(self::CLOSE),
        ];
    }
}