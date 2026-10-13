<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceService;
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
            'users' => User::where('admin_status', false)->orderBy('id')->get(),
            'attendanceRecords' => $attendanceRecords,
        ]);
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
