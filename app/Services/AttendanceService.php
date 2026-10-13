<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public static function parseMonth(?string $month): Carbon
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month) && checkdate((int) substr($month, 5, 2), 1, (int) substr($month, 0, 4))) {
            return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        }

        return Carbon::now()->startOfMonth();
    }

    public static function parseDay(?string $day): Carbon
    {
        if ($day && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $day, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return Carbon::createFromFormat('Y-m-d', $day)->startOfDay();
        }

        return Carbon::today();
    }

    public function monthlyRows(User $user, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $records = $user->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn(AttendanceRecord $record) => $record->date->toDateString());

        $rows = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $record = $records->get($day->toDateString());

            $rows[] = [
                'id' => $record?->id,
                'date' => $day->copy()->locale('ja')->isoFormat('MM/DD(ddd)'),
                'clock_in' => AttendanceRecord::toHourMinute($record?->clock_in),
                'clock_out' => AttendanceRecord::toHourMinute($record?->clock_out),
                'total_break_time' => $record?->total_break_time,
                'total_time' => $record?->total_time,
            ];
        }

        return $rows;
    }

    public function detailData(AttendanceRecord $record, ?StampCorrectionRequest $pending): array
    {
        $source = $pending ?? $record;

        return [
            'id' => $record->id,
            'year' => $record->date->format('Y年'),
            'date' => $record->date->format('n月j日'),
            'clock_in' => AttendanceRecord::toHourMinute($source->clock_in),
            'clock_out' => AttendanceRecord::toHourMinute($source->clock_out),
            'breaks' => $source->breaks->map(fn($break) => [
                'break_in' => AttendanceRecord::toHourMinute($break->break_in),
                'break_out' => AttendanceRecord::toHourMinute($break->break_out),
            ])->values()->all(),
            'comment' => $source->comment,
            'application' => $pending,
        ];
    }

    public function overwrite(AttendanceRecord $record, string $clockIn, string $clockOut, array $breaks, ?string $comment): void
    {
        DB::transaction(function () use ($record, $clockIn, $clockOut, $breaks, $comment) {
            $record->update([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'comment' => $comment,
            ]);

            $record->breaks()->delete();
            $record->breaks()->createMany($breaks);
        });
    }

    public function approve(StampCorrectionRequest $application): void
    {
        DB::transaction(function () use ($application) {
            $breaks = $application->breaks
                ->map(fn($break) => ['break_in' => $break->break_in, 'break_out' => $break->break_out])
                ->all();

            $this->overwrite(
                $application->attendanceRecord,
                $application->clock_in,
                $application->clock_out,
                $breaks,
                $application->comment,
            );

            $application->update(['status' => StampCorrectionRequest::STATUS_APPROVED]);
        });
    }
}
