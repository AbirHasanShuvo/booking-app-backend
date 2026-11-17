<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['scope' => 'nullable|in:upcoming,past']);

        $appointments = Appointment::with('service:id,name,image_url,price,duration_minutes')
            ->where('user_id', auth('api')->id())
            ->when($request->scope === 'upcoming', fn ($q) => $q
                ->where('start_at', '>=', now())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('start_at'))
            ->when($request->scope === 'past', fn ($q) => $q
                ->where(fn ($w) => $w->where('start_at', '<', now())
                    ->orWhereIn('status', ['cancelled', 'completed']))
                ->orderByDesc('start_at'))
            ->when(! $request->scope, fn ($q) => $q->orderByDesc('start_at'))
            ->get();

        return $this->ok($appointments, 'Appointments retrieved');
    }

    public function show(int $id)
    {
        $appointment = Appointment::with('service:id,name,image_url,price,duration_minutes')
            ->where('user_id', auth('api')->id())
            ->find($id);

        return $appointment
            ? $this->ok($appointment, 'OK')
            : $this->fail('Appointment not found', 404);
    }

    public function store(Request $request, AvailabilityService $availability)
    {
        $data = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'start_at'   => 'required|date_format:Y-m-d H:i',
            'notes'      => 'nullable|string|max:500',
        ]);

        $start = Carbon::createFromFormat('Y-m-d H:i', $data['start_at']);

        if ($start->lte(now())) {
            return $this->fail('Cannot book a time in the past', 422);
        }

        return DB::transaction(function () use ($data, $start, $availability) {
            $service = Service::where('is_active', true)
                ->lockForUpdate()
                ->find($data['service_id']);

            if (! $service) {
                return $this->fail('Service not found', 404);
            }

            if (! $availability->isValidSlot($service, $start)) {
                return $this->fail('Selected time is outside working hours or not a valid slot', 422);
            }

            $end = $start->copy()->addMinutes($service->duration_minutes);

            if ($availability->hasConflict($service, $start, $end)) {
                return $this->fail('This time slot is no longer available', 409);
            }

            $appointment = Appointment::create([
                'user_id'        => auth('api')->id(),
                'service_id'     => $service->id,
                'start_at'       => $start,
                'end_at'         => $end,
                'status'         => 'pending',
                'notes'          => $data['notes'] ?? null,
                'price_snapshot' => $service->price,
            ]);

            return $this->ok($appointment->load('service:id,name,image_url,price,duration_minutes'), 'Appointment booked', 201);
        });
    }

    public function cancel(int $id)
    {
        $appointment = Appointment::where('user_id', auth('api')->id())->find($id);

        if (! $appointment) {
            return $this->fail('Appointment not found', 404);
        }

        if ($appointment->status !== 'pending') {
            return $this->fail('Only pending appointments can be cancelled', 422);
        }

        $appointment->update(['status' => 'cancelled']);

        return $this->ok($appointment, 'Appointment cancelled');
    }

    private function ok($data, string $message, int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function fail(string $message, int $status)
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => (object) []], $status);
    }
}