<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService)
    {
    }

    public function index(Request $request): View
    {
        $date = AttendanceService::parseDay($request->query('date'));

        $attendanceRecords = AttendanceRecord::with(['user', 'breaks'])
            ->whereDate('date', $date)
            ->whereHas('user', fn($query) => $query->where('admin_status', false))
            ->orderBy('user_id')
            ->get();

        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $date->copy()->subDay()->toDateString(),
            'nextDay' => $date->copy()->addDay()->toDateString(),
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    public function show(AttendanceRecord $attendanceRecord): View
    {
        return view('admin.admin-detail', [
            'user' => $attendanceRecord->user,
            'attendanceRecord' => $this->attendanceService->detailData(
                $attendanceRecord->load('breaks'),
                $attendanceRecord->pendingCorrectionRequest()
            ),
        ]);
    }

    public function update(AttendanceCorrectionRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        if (!$attendanceRecord->pendingCorrectionRequest()) {
            $this->attendanceService->overwrite(
                $attendanceRecord,
                $request->input('new_clock_in'),
                $request->input('new_clock_out'),
                $request->breaks(),
                $request->input('comment'),
            );
        }

        return redirect('/admin/attendance/' . $attendanceRecord->id);
    }

    public function staff(Request $request, User $user): View
    {
        $date = AttendanceService::parseMonth($request->query('date'));

        return view('admin.staff-attendance-list', [
            'user' => $user,
            'date' => $date,
            'previousMonth' => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $this->attendanceService->monthlyRows($user, $date),
        ]);
    }
}
