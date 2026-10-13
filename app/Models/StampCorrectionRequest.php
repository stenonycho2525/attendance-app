<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = '承認待ち';
    public const STATUS_APPROVED = '承認済み';

    protected $fillable = [
        'attendance_record_id',
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(StampCorrectionRequestBreak::class)->orderBy('break_in');
    }

    public function proposalBreaks(): HasMany
    {
        return $this->breaks();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getApprovalStatusAttribute(): string
    {
        return $this->status;
    }

    public function getApplicationDateAttribute(): Carbon
    {
        return $this->created_at;
    }

    public function getNewDateAttribute(): Carbon
    {
        return $this->date;
    }

    public function getNewClockInAttribute(): string
    {
        return AttendanceRecord::toHourMinute($this->clock_in);
    }

    public function getNewClockOutAttribute(): string
    {
        return AttendanceRecord::toHourMinute($this->clock_out);
    }
}
