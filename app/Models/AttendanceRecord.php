<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_OFF_DUTY = '勤務外';
    public const STATUS_WORKING = '出勤中';
    public const STATUS_ON_BREAK = '休憩中';
    public const STATUS_FINISHED = '退勤済';

    protected $fillable = ['user_id', 'date', 'clock_in', 'clock_out', 'comment'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class)->orderBy('break_in');
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    public function pendingCorrectionRequest(): ?StampCorrectionRequest
    {
        return $this->correctionRequests()
            ->where('status', StampCorrectionRequest::STATUS_PENDING)
            ->with('breaks')
            ->latest('id')
            ->first();
    }
    public function getStatusAttribute(): string
    {
        if ($this->clock_out) {
            return self::STATUS_FINISHED;
        }

        return $this->breaks()->whereNull('break_out')->exists()
            ? self::STATUS_ON_BREAK
            : self::STATUS_WORKING;
    }
    public function getTotalBreakTimeAttribute(): ?string
    {
        $finishedBreaks = $this->breaks->filter(fn(AttendanceBreak $break) => $break->break_out);

        if ($finishedBreaks->isEmpty()) {
            return null;
        }

        return self::formatSeconds($finishedBreaks->sum(fn(AttendanceBreak $break) => $break->durationInSeconds()));
    }
    public function getTotalTimeAttribute(): ?string
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }

        $workSeconds = self::secondsOf($this->clock_out) - self::secondsOf($this->clock_in);
        $breakSeconds = $this->breaks
            ->filter(fn(AttendanceBreak $break) => $break->break_out)
            ->sum(fn(AttendanceBreak $break) => $break->durationInSeconds());

        return self::formatSeconds(max(0, $workSeconds - $breakSeconds));
    }

    public static function toHourMinute(?string $time): string
    {
        return $time ? Carbon::parse($time)->format('H:i') : '';
    }

    public static function secondsOf(string $time): int
    {
        return Carbon::parse($time)->secondsSinceMidnight();
    }

    public static function formatSeconds(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}