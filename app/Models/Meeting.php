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
    ];

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
}
