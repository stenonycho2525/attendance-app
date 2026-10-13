<?php

namespace App\Http\Controllers;

use App\Models\StampCorrectionRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = StampCorrectionRequest::with(['user', 'attendanceRecord'])->latest();
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $applications = $query->get();

        if ($user->isAdmin()) {
            return view('admin.admin-application-list', [
                'applications' => $applications,
            ]);
        }

        return view('user.user-application-list', [
            'user' => $user,
            'formattedApplications' => $applications->map(fn(StampCorrectionRequest $application) => [
                'id' => $application->id,
                'approval_status' => $application->approval_status,
                'date' => $application->date->format('Y/m/d'),
                'comment' => $application->comment,
                'application_date' => $application->application_date->format('Y/m/d'),
            ])->all(),
        ]);
    }

    public function redirectToAttendance(Request $request, StampCorrectionRequest $stampCorrectionRequest): RedirectResponse
    {
        $user = $request->user();
        abort_if(!$user->isAdmin() && (int) $stampCorrectionRequest->user_id !== $user->id, 404);

        return redirect('/attendance/' . $stampCorrectionRequest->attendance_record_id);
    }

    public function show(StampCorrectionRequest $stampCorrectionRequest): View
    {
        return view('admin.admin-application-detail', [
            'user' => $stampCorrectionRequest->user,
            'application' => $stampCorrectionRequest,
        ]);
    }

    public function approve(StampCorrectionRequest $stampCorrectionRequest, AttendanceService $attendanceService): RedirectResponse
    {
        if ($stampCorrectionRequest->isPending()) {
            $attendanceService->approve($stampCorrectionRequest->load(['breaks', 'attendanceRecord']));
        }

        return redirect('/stamp_correction_request/approve/' . $stampCorrectionRequest->id);
    }
}
