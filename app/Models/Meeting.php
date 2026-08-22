<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'minutes_signed_at' => 'datetime',
        'attendance_signed_at' => 'datetime',
        'photos_signed_at' => 'datetime',
    ];

    public function minutesSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'minutes_signed_by');
    }

    public function attendanceSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_signed_by');
    }

    public function photosSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photos_signed_by');
    }

    public function minutes(): HasOne
    {
        return $this->hasOne(MeetingMinute::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MeetingPhoto::class);
    }

    public function documentations(): HasMany
    {
        return $this->photos();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Check if a specific document type has been electronically signed.
     */
    public function isSigned(string $type): bool
    {
        return match ($type) {
            'minutes' => !empty($this->minutes_signed_at),
            'attendance' => !empty($this->attendance_signed_at),
            'photos', 'documentation' => !empty($this->photos_signed_at),
            default => false,
        };
    }

    /**
     * Check if all 3 documents for this meeting are signed.
     */
    public function isFullySigned(): bool
    {
        return !empty($this->minutes_signed_at)
            && !empty($this->attendance_signed_at)
            && !empty($this->photos_signed_at);
    }

    /**
     * Count how many documents are currently waiting for TTE.
     */
    public function pendingTteCount(): int
    {
        $count = 0;
        if (empty($this->minutes_signed_at)) $count++;
        if (empty($this->attendance_signed_at)) $count++;
        if (empty($this->photos_signed_at)) $count++;
        return $count;
    }
}
