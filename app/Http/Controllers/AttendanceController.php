<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Http\Requests\AttendanceStampRequest;
use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService)
    {
    }

    public function index(): View
    {
        $now = Carbon::now()->locale('ja');

        return view('user.attendance-register', [
            'user' => auth()->user(),
            'formattedDate' => $now->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => $now->format('H:i'),
        ]);
    }
    public function store(AttendanceStampRequest $request): RedirectResponse
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
    public function list(Request $request): View
    {
        $date = AttendanceService::parseMonth($request->query('date'));

        return view('user.user-attendance-list', [
            'date' => $date,
            'previousMonth' => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $this->attendanceService->monthlyRows($request->user(), $date),
        ]);
    }
    public function show(Request $request, AttendanceRecord $attendanceRecord): View
    {
        $user = $request->user();
        $data = $this->attendanceService->detailData(
            $attendanceRecord->load('breaks'),
            $attendanceRecord->pendingCorrectionRequest()
        );

        if ($user->isAdmin()) {
            return view('admin.admin-detail', [
                'user' => $attendanceRecord->user,
                'attendanceRecord' => $data,
            ]);
        }

        $this->ensureOwner($user, $attendanceRecord);

        return view('user.user-detail', [
            'user' => $user,
            'data' => $data,
        ]);
    }
    public function update(AttendanceCorrectionRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            $this->ensureOwner($user, $attendanceRecord);
        }

        if ($attendanceRecord->pendingCorrectionRequest()) {
            return redirect('/attendance/' . $attendanceRecord->id);
        }

        if ($user->isAdmin()) {
            $this->attendanceService->overwrite(
                $attendanceRecord,
                $request->input('new_clock_in'),
                $request->input('new_clock_out'),
                $request->breaks(),
                $request->input('comment'),
            );

            return redirect('/attendance/' . $attendanceRecord->id);
        }

        DB::transaction(function () use ($request, $attendanceRecord) {
            $application = StampCorrectionRequest::create([
                'attendance_record_id' => $attendanceRecord->id,
                'user_id' => $request->user()->id,
                'date' => $attendanceRecord->date,
                'clock_in' => $request->input('new_clock_in'),
                'clock_out' => $request->input('new_clock_out'),
                'comment' => $request->input('comment'),
                'status' => StampCorrectionRequest::STATUS_PENDING,
            ]);
            $application->breaks()->createMany($request->breaks());
        });

        return redirect('/attendance/' . $attendanceRecord->id);
    }
    private function ensureOwner(User $user, AttendanceRecord $attendanceRecord): void
    {
        abort_if((int) $attendanceRecord->user_id !== $user->id, 404);
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