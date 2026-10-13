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

        return view($user->isAdmin() ? 'admin.admin-application-list' : 'user.user-application-list', [
            'pendingApplications' => $applications->where('status', StampCorrectionRequest::STATUS_PENDING),
            'approvedApplications' => $applications->where('status', StampCorrectionRequest::STATUS_APPROVED),
        ]);
    }

    public function show(StampCorrectionRequest $stampCorrectionRequest): View
    {
        return view('admin.admin-application-detail', [
            'application' => $stampCorrectionRequest->load(['user', 'breaks']),
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
