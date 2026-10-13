<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
