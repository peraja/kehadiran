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

    protected $fillable = [
        'opd_id',
        'created_by',
        'title',
        'agenda',
        'date',
        'start_time',
        'end_time',
        'location',
        'status',
        'signer_title',
        'signer_name',
        'signer_nip',
        'signer_rank',
    ];

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
        return $this->hasMany(MeetingAttendance::class)->orderBy('check_in', 'asc')->orderBy('id', 'asc');
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
     * Check if the given user is the designated signer for this meeting.
     */
    public function isSigner(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return false;
        }

        // TTE is exclusively for users with role 'pimpinan'
        if (!$user->hasRole('pimpinan') && !$user->hasActiveRole('pimpinan')) {
            return false;
        }

        $userNip = preg_replace('/\D/', '', (string) $user->nip);
        $signerNip = preg_replace('/\D/', '', (string) $this->signer_nip);

        // 1. Direct match with meeting signer NIP or name
        if (!empty($userNip) && !empty($signerNip) && $userNip === $signerNip) {
            return true;
        }
        if (!empty($user->name) && !empty($this->signer_name) && strcasecmp(trim($user->name), trim($this->signer_name)) === 0) {
            return true;
        }

        // 2. Check if meeting is assigned to an OPD where this user is the Kepala OPD
        $opd = $this->opd;
        if ($opd) {
            $opdLeaderNip = preg_replace('/\D/', '', (string) $opd->leader_nip);
            if (!empty($userNip) && !empty($opdLeaderNip) && $userNip === $opdLeaderNip) {
                return true;
            }
            if (!empty($user->name) && !empty($opd->leader_name) && strcasecmp(trim($user->name), trim($opd->leader_name)) === 0) {
                return true;
            }
        }

        // 3. Check if user is a registered signer in this OPD
        if ($opd && !empty($userNip)) {
            $isOpdSigner = $opd->signers()->whereRaw("REPLACE(REPLACE(nip, ' ', ''), '-', '') = ?", [$userNip])->exists();
            if ($isOpdSigner) {
                return true;
            }
        }

        // 4. Fallback: If meeting has no specific signer designated, allow Pimpinan of the meeting's OPD
        if (empty($signerNip) && empty($this->signer_name)) {
            if ($opd && $user->unit_name && strcasecmp(trim($user->unit_name), trim($opd->name)) === 0) {
                return true;
            }
        }

        return false;
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

    /**
     * Check if a specific document type has actual content.
     */
    public function hasDocumentContent(string $type): bool
    {
        return match ($type) {
            'minutes' => !empty(trim((string)($this->minutes?->content ?? ''))),
            'attendance' => $this->attendances()->count() > 0,
            'photos', 'documentation' => $this->photos()->count() > 0,
            default => false,
        };
    }

    /**
     * Check if at least one document has actual content.
     */
    public function hasAnyDocumentContent(): bool
    {
        return $this->hasDocumentContent('minutes')
            || $this->hasDocumentContent('attendance')
            || $this->hasDocumentContent('photos');
    }

    /**
     * Count how many documents have content and are eligible for TTE.
     */
    public function readyForTteCount(): int
    {
        $count = 0;
        if (empty($this->attendance_signed_at) && $this->hasDocumentContent('attendance')) $count++;
        if (empty($this->photos_signed_at) && $this->hasDocumentContent('photos')) $count++;
        if (empty($this->minutes_signed_at) && $this->hasDocumentContent('minutes')) $count++;
        return $count;
    }

    /**
     * Count how many documents have already been signed with TTE.
     */
    public function signedTteCount(): int
    {
        $count = 0;
        if (!empty($this->attendance_signed_at)) $count++;
        if (!empty($this->photos_signed_at)) $count++;
        if (!empty($this->minutes_signed_at)) $count++;
        return $count;
    }
}
