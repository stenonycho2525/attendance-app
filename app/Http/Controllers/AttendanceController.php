<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttendanceStampRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $now = Carbon::now();

        return view('user.attendance-register', [
            'user' => auth()->user(),
            'formattedDate' => $now->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => $now->format('H:i'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttendanceStampRequest $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        DB::transaction(function () use ($request, $user, $now) {
            match ($request->input('action')) {
                'clock_in' => $this->clockIn($user, $now),
                'break_in' => $this->breakIn($user, $now),
                'break_out' => $this->breakOut($user, $now),
                'clock_out' => $this->clockOut($user, $now),
            };
        });

        return redirect('/attendance');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    private function clockIn(User $user, Carbon $now): void
    {
        AttendanceRecord::firstOrCreate(
            ['user_id' => $user->id, 'date' => $now->toDateString()],
            ['clock_in' => $now->format('H:i:s')]
        );
    }
    private function breakIn(User $user, Carbon $now): void
    {
        $record = $this->findTodayRecord($user, $now);

        if ($record?->status !== AttendanceRecord::STATUS_WORKING) {
            return;
        }

        $record->breaks()->create(['break_in' => $now->format('H:i:s')]);
    }
    private function breakOut(User $user, Carbon $now): void
    {
        $record = $this->findTodayRecord($user, $now);

        if ($record?->status !== AttendanceRecord::STATUS_ON_BREAK) {
            return;
        }

        $record->breaks()
            ->whereNull('break_out')
            ->latest('id')
            ->first()
            ->update(['break_out' => $now->format('H:i:s')]);
    }
    private function clockOut(User $user, Carbon $now): void
    {
        $record = $this->findTodayRecord($user, $now);

        if ($record?->status !== AttendanceRecord::STATUS_WORKING) {
            return;
        }

        $record->update(['clock_out' => $now->format('H:i:s')]);
    }
    private function findTodayRecord(User $user, Carbon $now): ?AttendanceRecord
    {
        return AttendanceRecord::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->lockForUpdate()
            ->first();
    }
}
